package com.bnhs.edutrack.profile

import android.content.Context
import com.bnhs.edutrack.auth.AuthSession
import com.bnhs.edutrack.auth.AuthUser
import com.bnhs.edutrack.auth.SessionStore
import com.bnhs.edutrack.network.ApiClient
import com.bnhs.edutrack.network.LmsApiService
import com.bnhs.edutrack.network.MobileProfileDto
import com.bnhs.edutrack.network.UpdateProfileRequest

sealed class ProfileResult<out T> {
    data class Success<T>(val value: T) : ProfileResult<T>()
    data class Error(val message: String) : ProfileResult<Nothing>()
}

class ProfileRepository(context: Context) {

    private val sessionStore = SessionStore(context.applicationContext)
    private val api: LmsApiService = ApiClient.createLmsApi(sessionStore)

    suspend fun loadProfile(): ProfileResult<MobileProfileDto> {
        val auth = sessionStore.bearerAuthorization()
            ?: return ProfileResult.Error("Not signed in.")
        return try {
            val response = api.getProfile(auth)
            if (response.isSuccessful) {
                val data = response.body()?.data
                if (data != null) ProfileResult.Success(data) else ProfileResult.Error("Profile not found.")
            } else {
                ProfileResult.Error(ApiClient.parseErrorMessage(response))
            }
        } catch (e: Exception) {
            ProfileResult.Error(e.localizedMessage ?: "Could not load profile.")
        }
    }

    suspend fun saveProfile(
        firstName: String,
        middleName: String?,
        lastName: String,
        suffix: String?,
        phone: String,
    ): ProfileResult<MobileProfileDto> {
        val auth = sessionStore.bearerAuthorization()
            ?: return ProfileResult.Error("Not signed in.")
        return try {
            val response = api.updateProfile(
                auth,
                UpdateProfileRequest(
                    firstName = firstName.trim(),
                    middleName = middleName?.trim()?.takeIf { it.isNotEmpty() },
                    lastName = lastName.trim(),
                    suffix = suffix?.trim()?.takeIf { it.isNotEmpty() },
                    phone = phone.trim(),
                ),
            )
            if (response.isSuccessful) {
                val data = response.body()?.data
                if (data != null) {
                    syncSessionFromProfile(data)
                    ProfileResult.Success(data)
                } else {
                    ProfileResult.Error("Profile update failed.")
                }
            } else {
                ProfileResult.Error(ApiClient.parseErrorMessage(response))
            }
        } catch (e: Exception) {
            ProfileResult.Error(e.localizedMessage ?: "Could not save profile.")
        }
    }

    fun syncSessionFromProfile(dto: MobileProfileDto) {
        val session = sessionStore.loadSession() ?: return
        val updated = session.copy(
            user = AuthUser(
                id = session.user.id,
                name = dto.name.orEmpty().ifBlank { session.user.name },
                email = dto.email.orEmpty().ifBlank { session.user.email },
                roles = dto.roles ?: session.user.roles,
                permissions = dto.permissions ?: session.user.permissions,
                phone = dto.phone,
            ),
        )
        sessionStore.saveSession(updated)
    }

    fun currentSession(): AuthSession? = sessionStore.loadSession()

    fun apiBaseUrl(): String = sessionStore.getApiBaseUrl()

    companion object {
        @Volatile
        private var instance: ProfileRepository? = null

        fun get(context: Context): ProfileRepository {
            return instance ?: synchronized(this) {
                instance ?: ProfileRepository(context).also { instance = it }
            }
        }
    }
}
