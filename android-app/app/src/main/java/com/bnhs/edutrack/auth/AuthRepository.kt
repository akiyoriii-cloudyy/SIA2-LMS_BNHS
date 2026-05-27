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

    fun sanitizeApiBaseUrl(): Boolean = sessionStore.sanitizeSavedApiUrlIfNeeded()

    fun setApiBaseUrl(url: String) {
        val normalized = SessionStore.normalizeBaseUrl(url)
        if (SessionStore.isStaleOrInvalidApiUrl(normalized)) {
            sessionStore.setApiBaseUrl(SessionStore.defaultLocalApiUrl())
        } else {
            sessionStore.setApiBaseUrl(normalized)
        }
    }

    fun resetApiBaseUrlToDefault() {
        sessionStore.clearApiBaseUrlOverride()
    }

    suspend fun testConnection(): ConnectionTestResult = withContext(Dispatchers.IO) {
        val baseUrl = sessionStore.getApiBaseUrl()
        if (baseUrl.isBlank()) {
            return@withContext ConnectionTestResult.Error("API URL is empty. Open Server settings and set a URL.")
        }
        try {
            val response = api().health()
            if (response.isSuccessful && response.body()?.ok == true) {
                ConnectionTestResult.Success(baseUrl)
            } else {
                ConnectionTestResult.Error(
                    "Server responded with HTTP ${response.code()} at:\n$baseUrl\n" +
                        "Use: http://10.0.2.2/LMS_BNHS/public/api/ (emulator) or http://YOUR_PC_IP/LMS_BNHS/public/api/ (phone).",
                )
            }
        } catch (e: UnknownHostException) {
            ConnectionTestResult.Error(connectionHelpMessage(baseUrl, e))
        } catch (e: SocketTimeoutException) {
            ConnectionTestResult.Error(
                "Connection timed out for:\n$baseUrl\nStart Apache in XAMPP and confirm the URL in a phone browser.",
            )
        } catch (e: IOException) {
            ConnectionTestResult.Error(connectionHelpMessage(baseUrl, e))
        } catch (e: Exception) {
            ConnectionTestResult.Error(e.message ?: "Could not reach the server.")
        }
    }

    suspend fun login(email: String, password: String): LoginResult =
        loginAttempt(email, password, allowUrlSanitizeRetry = true)

    private suspend fun loginAttempt(
        email: String,
        password: String,
        allowUrlSanitizeRetry: Boolean,
    ): LoginResult = withContext(Dispatchers.IO) {
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
            if (allowUrlSanitizeRetry && sessionStore.sanitizeSavedApiUrlIfNeeded()) {
                return@withContext loginAttempt(email, password, allowUrlSanitizeRetry = false)
            }
            val msg = connectionHelpMessage(sessionStore.getApiBaseUrl(), e)
            logLoginFailure(trimmedEmail, msg)
            LoginResult.Error(msg)
        } catch (e: SocketTimeoutException) {
            val msg =
                "Connection timed out for:\n${sessionStore.getApiBaseUrl()}\n" +
                    "Start Apache in XAMPP, then use Server settings → Test connection.\n" +
                    "Emulator: http://10.0.2.2/LMS_BNHS/public/api/\n" +
                    "Phone: http://YOUR_PC_IP/LMS_BNHS/public/api/"
            logLoginFailure(trimmedEmail, msg)
            LoginResult.Error(msg)
        } catch (e: IOException) {
            val msg = connectionHelpMessage(sessionStore.getApiBaseUrl(), e)
            logLoginFailure(trimmedEmail, msg)
            LoginResult.Error(msg)
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
            ForgotPasswordResult.Error(connectionHelpMessage(sessionStore.getApiBaseUrl(), e))
        } catch (e: SocketTimeoutException) {
            ForgotPasswordResult.Error("Connection timed out. Check your API URL and network.")
        } catch (e: IOException) {
            ForgotPasswordResult.Error(connectionHelpMessage(sessionStore.getApiBaseUrl(), e))
        } catch (e: Exception) {
            ForgotPasswordResult.Error(e.message ?: "Could not send reset request.")
        }
    }

    private fun connectionHelpMessage(baseUrl: String, cause: Exception? = null): String {
        val hint = when {
            baseUrl.contains("trycloudflare.com", ignoreCase = true) ->
                "The saved URL is an old Cloudflare tunnel. Tap \"Use XAMPP (emulator)\" below, Save, then Test connection."
            baseUrl.contains("/LMS_BNHS/api/", ignoreCase = true) &&
                !baseUrl.contains("/public/", ignoreCase = true) ->
                "URL may be missing /public/. Try: http://10.0.2.2/LMS_BNHS/public/api/"
            else -> "Emulator: http://10.0.2.2/LMS_BNHS/public/api/ — Phone: http://YOUR_PC_IP/LMS_BNHS/public/api/"
        }
        val detail = cause?.message?.takeIf { it.isNotBlank() }?.let { "\n($it)" }.orEmpty()
        return "Cannot reach the server at:\n$baseUrl$detail\n\nStart Apache/MySQL in XAMPP. Open that URL in a browser on your PC first.\n$hint"
    }

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
