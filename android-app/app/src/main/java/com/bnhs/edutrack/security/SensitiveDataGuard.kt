package com.bnhs.edutrack.security

import android.content.Context
import com.bnhs.edutrack.data.ParentEntity
import com.bnhs.edutrack.data.UserAccountEntity

/**
 * Central guard for encrypting/decrypting sensitive columns before SQLite I/O.
 */
class SensitiveDataGuard private constructor(
    private val encryptor: FieldEncryptor,
) {
    fun encryptField(value: String): String =
        if (value.isBlank() || encryptor.isEncrypted(value)) value else encryptor.encrypt(value)

    fun decryptField(value: String): String =
        if (value.isBlank()) value else encryptor.decrypt(value)

    fun parentForStorage(parent: ParentEntity): ParentEntity =
        parent.copy(
            contact = encryptField(parent.contact),
            email = encryptField(parent.email),
        )

    fun parentForDisplay(parent: ParentEntity): ParentEntity =
        parent.copy(
            contact = decryptField(parent.contact),
            email = decryptField(parent.email),
        )

    fun userForStorage(username: String, plainPassword: String, role: String, displayName: String, assignment: String): UserAccountEntity {
        val hashed = PasswordHasher.hash(plainPassword)
        return UserAccountEntity(
            username = username,
            passwordHash = hashed.hash,
            passwordSalt = hashed.salt,
            role = role,
            displayName = displayName,
            assignment = assignment,
        )
    }

    fun verifyPassword(account: UserAccountEntity, plainPassword: String): Boolean =
        PasswordHasher.verify(plainPassword, account.passwordHash, account.passwordSalt)

    companion object {
        @Volatile
        private var instance: SensitiveDataGuard? = null

        fun get(context: Context): SensitiveDataGuard =
            instance ?: synchronized(this) {
                instance ?: SensitiveDataGuard(FieldEncryptor.get(context)).also { instance = it }
            }
    }
}
