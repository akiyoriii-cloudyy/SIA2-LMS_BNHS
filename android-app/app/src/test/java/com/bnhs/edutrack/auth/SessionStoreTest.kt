package com.bnhs.edutrack.auth

import org.junit.Assert.assertEquals
import org.junit.Test

class SessionStoreTest {

    @Test
    fun normalizeBaseUrl_adds_scheme_and_trailing_slash() {
        assertEquals(
            "http://10.0.2.2/LMS_BNHS/public/api/",
            SessionStore.normalizeBaseUrl("10.0.2.2/LMS_BNHS/public/api"),
        )
    }

    @Test
    fun normalizeBaseUrl_preserves_https() {
        assertEquals(
            "https://school.example.com/api/",
            SessionStore.normalizeBaseUrl("https://school.example.com/api"),
        )
    }
}
