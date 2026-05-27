package com.bnhs.edutrack.security

import android.content.Context
import android.util.Base64
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import java.security.SecureRandom
import javax.crypto.Cipher
import javax.crypto.spec.GCMParameterSpec
import javax.crypto.spec.SecretKeySpec

/**
 * AES-256-GCM field-level encryption for PII stored in SQLite (parent contacts, etc.).
 */
class FieldEncryptor(context: Context) {

    private val prefs = run {
        val masterKey = MasterKey.Builder(context.applicationContext)
            .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
            .build()
        EncryptedSharedPreferences.create(
            context.applicationContext,
            PREFS_NAME,
            masterKey,
            EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
            EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM,
        )
    }

    private val secretKey: SecretKeySpec by lazy {
        val existing = prefs.getString(KEY_BYTES, null)
        if (existing != null) {
            SecretKeySpec(Base64.decode(existing, Base64.NO_WRAP), "AES")
        } else {
            val raw = ByteArray(32).also { SecureRandom().nextBytes(it) }
            prefs.edit().putString(KEY_BYTES, Base64.encodeToString(raw, Base64.NO_WRAP)).apply()
            SecretKeySpec(raw, "AES")
        }
    }

    fun encrypt(plainText: String): String {
        if (plainText.isEmpty()) return plainText
        val iv = ByteArray(12).also { SecureRandom().nextBytes(it) }
        val cipher = Cipher.getInstance(TRANSFORM)
        cipher.init(Cipher.ENCRYPT_MODE, secretKey, GCMParameterSpec(128, iv))
        val encrypted = cipher.doFinal(plainText.toByteArray(Charsets.UTF_8))
        val payload = iv + encrypted
        return PREFIX + Base64.encodeToString(payload, Base64.NO_WRAP)
    }

    fun decrypt(cipherText: String): String {
        if (!isEncrypted(cipherText)) return cipherText
        val raw = Base64.decode(cipherText.removePrefix(PREFIX), Base64.NO_WRAP)
        val iv = raw.copyOfRange(0, 12)
        val body = raw.copyOfRange(12, raw.size)
        val cipher = Cipher.getInstance(TRANSFORM)
        cipher.init(Cipher.DECRYPT_MODE, secretKey, GCMParameterSpec(128, iv))
        return String(cipher.doFinal(body), Charsets.UTF_8)
    }

    fun isEncrypted(value: String): Boolean = value.startsWith(PREFIX)

    companion object {
        const val PREFIX = "BNHSENC:"
        private const val PREFS_NAME = "bnhs_field_crypto_v1"
        private const val KEY_BYTES = "aes_key"
        private const val TRANSFORM = "AES/GCM/NoPadding"

        @Volatile
        private var instance: FieldEncryptor? = null

        fun get(context: Context): FieldEncryptor =
            instance ?: synchronized(this) {
                instance ?: FieldEncryptor(context.applicationContext).also { instance = it }
            }
    }
}
