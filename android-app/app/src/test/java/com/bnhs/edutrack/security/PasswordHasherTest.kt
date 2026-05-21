package com.bnhs.edutrack.security

import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class PasswordHasherTest {

    @Test
    fun hashAndVerify_success() {
        val hashed = PasswordHasher.hash("password")
        assertTrue(PasswordHasher.verify("password", hashed.hash, hashed.salt))
    }

    @Test
    fun verify_rejectsWrongPassword() {
        val hashed = PasswordHasher.hash("password")
        assertFalse(PasswordHasher.verify("wrong", hashed.hash, hashed.salt))
    }

    @Test
    fun hash_usesUniqueSalt() {
        val a = PasswordHasher.hash("password")
        val b = PasswordHasher.hash("password")
        assertNotEquals(a.salt, b.salt)
        assertNotEquals(a.hash, b.hash)
    }
}
