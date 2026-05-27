package com.bnhs.edutrack.auth

import android.content.Context
import com.bnhs.edutrack.network.ApiClient
import com.bnhs.edutrack.tracking.ActivityAction
import com.bnhs.edutrack.tracking.ActivityCategory
import com.bnhs.edutrack.tracking.ActivityLogger
import com.bnhs.edutrack.tracking.SessionTracker
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.IOException
import java.net.SocketTimeoutException
import java.net.UnknownHostException

class AuthRepository private constructor(
    private val context: Context,
    private val sessionStore: SessionStore,
) {

    private val sessionTracker = SessionTracker.get(context)
    private val activityLogger = ActivityLogger.get(context)

    private fun api(): AuthApiService = ApiClient.createAuthApi(sessionStore)

    suspend fun restoreSession(): AuthSession? = withContext(Dispatchers.IO) {
        val session = sessionStore.loadSession() ?: return@withContext null
        sessionTracker.ensureTracked(session)
        session
    }

    fun getApiBaseUrl(): String = sessionStore.getApiBaseUrl()

    fun setApiBaseUrl(url: String) {
        sessionStore.setApiBaseUrl(url)
    }

    suspend fun login(email: String, password: String): LoginResult = withContext(Dispatchers.IO) {
        val trimmedEmail = email.trim()
        if (trimmedEmail.isEmpty() || password.isEmpty()) {
            return@withContext LoginResult.Error("Email and password are required.")
        }
        if (!trimmedEmail.contains('@')) {
            return@withContext LoginResult.Error("Enter a valid email address.")
        }

        try {
            val response = api().login(LoginRequest(email = trimmedEmail, password = password))
            if (!response.isSuccessful) {
                val message = ApiClient.parseErrorMessage(response)
                activityLogger.log(
                    category = ActivityCategory.AUTH,
                    action = ActivityAction.LOGIN_FAILURE,
                    success = false,
                    actorEmail = trimmedEmail,
                    details = message,
                )
                return@withContext LoginResult.Error(message)
            }
            val body = response.body()
            val token = body?.token?.trim().orEmpty()
            val userDto = body?.user
            if (token.isEmpty() || userDto?.id == null || userDto.name.isNullOrBlank() || userDto.email.isNullOrBlank()) {
                return@withContext LoginResult.Error("Login succeeded but the server response was incomplete.")
            }
            val session = AuthSession(
                token = token,
                tokenType = body.tokenType?.trim().takeIf { !it.isNullOrBlank() } ?: "Bearer",
                user = AuthUser(
                    id = userDto.id,
                    name = userDto.name,
                    email = userDto.email,
                    roles = userDto.roles.orEmpty(),
                    permissions = userDto.permissions.orEmpty(),
                ),
            )
            sessionStore.saveSession(session)
            sessionTracker.startSession(session.user)
            LoginResult.Success(session)
        } catch (e: UnknownHostException) {
            logLoginFailure(trimmedEmail, connectionHelpMessage())
            LoginResult.Error(connectionHelpMessage())
        } catch (e: SocketTimeoutException) {
            val msg =
                "Connection timed out. Start Apache in XAMPP, open Server settings, and use:\n" +
                    "• Emulator: http://10.0.2.2/LMS_BNHS/public/api/\n" +
                    "• Phone (same Wi‑Fi): http://YOUR_PC_IP/LMS_BNHS/public/api/"
            logLoginFailure(trimmedEmail, msg)
            LoginResult.Error(msg)
        } catch (e: IOException) {
            logLoginFailure(trimmedEmail, connectionHelpMessage())
            LoginResult.Error(connectionHelpMessage())
        } catch (e: Exception) {
            val msg = e.message ?: "Unexpected login error."
            logLoginFailure(trimmedEmail, msg)
            LoginResult.Error(msg)
        }
    }

    private suspend fun logLoginFailure(email: String, details: String) {
        activityLogger.log(
            category = ActivityCategory.AUTH,
            action = ActivityAction.LOGIN_FAILURE,
            success = false,
            actorEmail = email,
            details = details,
        )
    }

    suspend fun touchSessionActivity() = withContext(Dispatchers.IO) {
        sessionTracker.touchActivity()
        sessionStore.touchLastActivity()
    }

    suspend fun logout(): Unit = withContext(Dispatchers.IO) {
        val auth = sessionStore.bearerAuthorization()
        if (auth != null) {
            try {
                api().logout(auth)
            } catch (_: Exception) {
                // Clear local session even if revoke fails (offline).
            }
        }
        sessionTracker.endSession("Signed out; token cleared locally.")
        sessionStore.clearSession()
    }

    suspend fun requestPasswordReset(email: String): ForgotPasswordResult = withContext(Dispatchers.IO) {
        val trimmedEmail = email.trim()
        if (trimmedEmail.isEmpty()) {
            return@withContext ForgotPasswordResult.Error("Email is required.")
        }
        if (!trimmedEmail.contains('@')) {
            return@withContext ForgotPasswordResult.Error("Enter a valid email address.")
        }

        try {
            val response = api().forgotPassword(ForgotPasswordRequest(trimmedEmail))
            if (!response.isSuccessful) {
                return@withContext ForgotPasswordResult.Error(ApiClient.parseErrorMessage(response))
            }
            val message = response.body()?.message?.trim().orEmpty()
            val ack = message.ifEmpty { "If that email is registered, a password reset link has been sent." }
            activityLogger.log(
                category = ActivityCategory.ACCOUNT,
                action = ActivityAction.PASSWORD_RESET_REQUEST,
                success = true,
                actorEmail = trimmedEmail,
                details = ack,
                sessionUuid = sessionStore.getTrackingSessionUuid(),
            )
            ForgotPasswordResult.Success(ack)
        } catch (e: UnknownHostException) {
            ForgotPasswordResult.Error(connectionHelpMessage())
        } catch (e: SocketTimeoutException) {
            ForgotPasswordResult.Error("Connection timed out. Check your API URL and network.")
        } catch (e: IOException) {
            ForgotPasswordResult.Error(connectionHelpMessage())
        } catch (e: Exception) {
            ForgotPasswordResult.Error(e.message ?: "Could not send reset request.")
        }
    }

    private fun connectionHelpMessage(): String =
        "Cannot reach the server. Start Apache/MySQL in XAMPP, confirm Laravel works in a browser, " +
            "and set the API URL (emulator: http://10.0.2.2/LMS_BNHS/public/api/)."

    companion object {
        @Volatile
        private var instance: AuthRepository? = null

        fun getInstance(context: Context): AuthRepository {
            return instance ?: synchronized(this) {
                instance ?: AuthRepository(
                    context.applicationContext,
                    SessionStore(context.applicationContext),
                ).also { instance = it }
            }
        }
    }
}
