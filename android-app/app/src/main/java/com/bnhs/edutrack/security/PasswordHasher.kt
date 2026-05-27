package com.bnhs.edutrack.security

import java.security.MessageDigest
import java.security.SecureRandom
import java.util.Base64

/**
 * Salted SHA-256 hashes for local [user_accounts] — never store plaintext passwords.
 */
object PasswordHasher {

    private const val SALT_BYTES = 16

    data class HashResult(val hash: String, val salt: String)

    fun hash(password: String): HashResult {
        val saltBytes = ByteArray(SALT_BYTES).also { SecureRandom().nextBytes(it) }
        val salt = Base64.getEncoder().encodeToString(saltBytes)
        val hash = digest(password, saltBytes)
        return HashResult(hash = hash, salt = salt)
    }

    fun verify(password: String, hash: String, salt: String): Boolean {
        val saltBytes = Base64.getDecoder().decode(salt)
        return digest(password, saltBytes) == hash
    }

    private fun digest(password: String, salt: ByteArray): String {
        val md = MessageDigest.getInstance("SHA-256")
        md.update(salt)
        md.update(password.toByteArray(Charsets.UTF_8))
        return Base64.getEncoder().encodeToString(md.digest())
    }
}
