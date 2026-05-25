package com.bnhs.edutrack.auth

import android.content.Context
import android.content.SharedPreferences
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import com.bnhs.edutrack.BuildConfig

/**
 * Encrypted token/session storage (token-based authentication).
 */
class SessionStore(context: Context) {

    private val prefs: SharedPreferences = run {
        val masterKey = MasterKey.Builder(context)
            .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
            .build()
        EncryptedSharedPreferences.create(
            context,
            PREFS_NAME,
            masterKey,
            EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
            EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM,
        )
    }

    fun getApiBaseUrl(): String {
        val saved = prefs.getString(KEY_API_BASE_URL, null)?.trim().orEmpty()
        if (saved.isNotEmpty()) return normalizeBaseUrl(saved)
        return normalizeBaseUrl(BuildConfig.API_BASE_URL)
    }

    fun setApiBaseUrl(url: String) {
        prefs.edit().putString(KEY_API_BASE_URL, normalizeBaseUrl(url)).apply()
    }

    fun saveSession(session: AuthSession) {
        prefs.edit()
            .putString(KEY_TOKEN, session.token)
            .putString(KEY_TOKEN_TYPE, session.tokenType)
            .putLong(KEY_USER_ID, session.user.id)
            .putString(KEY_USER_NAME, session.user.name)
            .putString(KEY_USER_EMAIL, session.user.email)
            .putString(KEY_USER_ROLES, session.user.roles.joinToString(ROLE_DELIMITER))
            .putString(KEY_USER_PERMISSIONS, session.user.permissions.joinToString(PERM_DELIMITER))
            .putString(KEY_USER_PHONE, session.user.phone.orEmpty())
            .apply()
    }

    fun saveTrackingSession(sessionUuid: String) {
        prefs.edit()
            .putString(KEY_TRACKING_SESSION_UUID, sessionUuid)
            .putLong(KEY_LAST_ACTIVITY_AT, System.currentTimeMillis())
            .apply()
    }

    fun getTrackingSessionUuid(): String? =
        prefs.getString(KEY_TRACKING_SESSION_UUID, null)?.trim()?.takeIf { it.isNotEmpty() }

    fun touchLastActivity() {
        prefs.edit().putLong(KEY_LAST_ACTIVITY_AT, System.currentTimeMillis()).apply()
    }

    fun clearTrackingSession() {
        prefs.edit()
            .remove(KEY_TRACKING_SESSION_UUID)
            .remove(KEY_LAST_ACTIVITY_AT)
            .apply()
    }

    fun loadSession(): AuthSession? {
        val token = prefs.getString(KEY_TOKEN, null)?.trim().orEmpty()
        if (token.isEmpty()) return null
        val userId = prefs.getLong(KEY_USER_ID, 0L)
        val name = prefs.getString(KEY_USER_NAME, null).orEmpty()
        val email = prefs.getString(KEY_USER_EMAIL, null).orEmpty()
        if (userId <= 0L || name.isEmpty() || email.isEmpty()) return null
        val rolesRaw = prefs.getString(KEY_USER_ROLES, "").orEmpty()
        val roles = if (rolesRaw.isBlank()) emptyList() else rolesRaw.split(ROLE_DELIMITER)
        val permsRaw = prefs.getString(KEY_USER_PERMISSIONS, "").orEmpty()
        val permissions = if (permsRaw.isBlank()) emptyList() else permsRaw.split(PERM_DELIMITER)
        val phone = prefs.getString(KEY_USER_PHONE, null)?.trim()?.takeIf { it.isNotEmpty() }

        return AuthSession(
            token = token,
            tokenType = prefs.getString(KEY_TOKEN_TYPE, "Bearer") ?: "Bearer",
            user = AuthUser(id = userId, name = name, email = email, roles = roles, permissions = permissions, phone = phone),
        )
    }

    fun clearSession() {
        prefs.edit()
            .remove(KEY_TOKEN)
            .remove(KEY_TOKEN_TYPE)
            .remove(KEY_USER_ID)
            .remove(KEY_USER_NAME)
            .remove(KEY_USER_EMAIL)
            .remove(KEY_USER_ROLES)
            .remove(KEY_USER_PERMISSIONS)
            .remove(KEY_USER_PHONE)
            .remove(KEY_TRACKING_SESSION_UUID)
            .remove(KEY_LAST_ACTIVITY_AT)
            .apply()
    }

    fun bearerAuthorization(): String? {
        val session = loadSession() ?: return null
        val type = session.tokenType.ifBlank { "Bearer" }
        return "$type ${session.token}"
    }

    companion object {
        private const val PREFS_NAME = "bnhs_auth_session_v1"
        private const val KEY_API_BASE_URL = "api_base_url"
        private const val KEY_TOKEN = "token"
        private const val KEY_TOKEN_TYPE = "token_type"
        private const val KEY_USER_ID = "user_id"
        private const val KEY_USER_NAME = "user_name"
        private const val KEY_USER_EMAIL = "user_email"
        private const val KEY_USER_ROLES = "user_roles"
        private const val KEY_USER_PERMISSIONS = "user_permissions"
        private const val KEY_USER_PHONE = "user_phone"
        private const val KEY_TRACKING_SESSION_UUID = "tracking_session_uuid"
        private const val KEY_LAST_ACTIVITY_AT = "last_activity_at"
        private const val ROLE_DELIMITER = "\u001F"
        private const val PERM_DELIMITER = "\u001E"

        fun normalizeBaseUrl(raw: String): String {
            var url = raw.trim()
            if (url.isEmpty()) return url
            if (!url.startsWith("http://") && !url.startsWith("https://")) {
                url = "http://$url"
            }
            if (!url.endsWith("/")) url += "/"
            return url
        }
    }
}
