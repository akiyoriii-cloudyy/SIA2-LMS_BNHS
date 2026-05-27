package com.bnhs.edutrack.network

import android.os.Build
import com.bnhs.edutrack.BuildConfig
import com.bnhs.edutrack.auth.SessionStore

/**
 * Picks the LMS API base URL for emulator vs physical device (USB debugging / LAN).
 * Users do not configure this in the app — login tries sensible defaults automatically.
 */
object ApiUrlResolver {

    private const val PROJECT_API_PATH = "LMS_BNHS/public/api/"

    fun isEmulator(): Boolean {
        return Build.FINGERPRINT.startsWith("generic")
            || Build.FINGERPRINT.startsWith("unknown")
            || Build.MODEL.contains("google_sdk", ignoreCase = true)
            || Build.MODEL.contains("Emulator", ignoreCase = true)
            || Build.MODEL.contains("Android SDK built for x86", ignoreCase = true)
            || Build.MANUFACTURER.contains("Genymotion", ignoreCase = true)
            || Build.HARDWARE.contains("goldfish", ignoreCase = true)
            || Build.HARDWARE.contains("ranchu", ignoreCase = true)
            || (Build.BRAND.startsWith("generic") && Build.DEVICE.startsWith("generic"))
            || "google_sdk" == Build.PRODUCT
    }

    fun isEmulatorOnlyHost(url: String): Boolean {
        val lower = url.lowercase()
        return lower.contains("10.0.2.2") || lower.contains("10.0.3.2")
    }

    fun defaultApiBaseUrl(): String {
        if (isEmulator()) {
            return SessionStore.normalizeBaseUrl("http://10.0.2.2/$PROJECT_API_PATH")
        }

        val lanHost = BuildConfig.DEV_LAN_API_HOST.trim()
        if (lanHost.isNotEmpty()) {
            return SessionStore.normalizeBaseUrl("http://$lanHost/$PROJECT_API_PATH")
        }

        // USB debugging: run `adb reverse tcp:80 tcp:80` so device localhost reaches XAMPP on the PC.
        return SessionStore.normalizeBaseUrl("http://127.0.0.1/$PROJECT_API_PATH")
    }

    fun loginCandidateUrls(currentUrl: String): List<String> {
        val normalizedCurrent = SessionStore.normalizeBaseUrl(currentUrl)
        val candidates = linkedSetOf<String>()

        candidates += defaultApiBaseUrl()

        if (isEmulator()) {
            candidates += SessionStore.normalizeBaseUrl("http://10.0.2.2/LMS_BNHS/api/")
            candidates += SessionStore.normalizeBaseUrl("http://10.0.2.2:8000/api/")
        } else {
            candidates += SessionStore.normalizeBaseUrl("http://127.0.0.1/$PROJECT_API_PATH")
            candidates += SessionStore.normalizeBaseUrl("http://127.0.0.1/LMS_BNHS/api/")
            val lanHost = BuildConfig.DEV_LAN_API_HOST.trim()
            if (lanHost.isNotEmpty()) {
                candidates += SessionStore.normalizeBaseUrl("http://$lanHost/$PROJECT_API_PATH")
                candidates += SessionStore.normalizeBaseUrl("http://$lanHost/LMS_BNHS/api/")
            }
        }

        if (normalizedCurrent.contains("/public/api/")) {
            candidates += SessionStore.normalizeBaseUrl(normalizedCurrent.replace("/public/api/", "/api/"))
        }
        if (normalizedCurrent.contains("/api/") && !normalizedCurrent.contains("/public/api/")) {
            candidates += SessionStore.normalizeBaseUrl(normalizedCurrent.replace("/api/", "/public/api/"))
        }

        candidates.remove(normalizedCurrent)
        return candidates.toList()
    }

    fun connectionHelpMessage(): String {
        return if (isEmulator()) {
            "Cannot reach the server. Start Apache and MySQL in XAMPP, open http://localhost/LMS_BNHS/public in a browser, then try again."
        } else {
            "Cannot reach the server. Start XAMPP, then either:\n" +
                "• USB debugging: run `adb reverse tcp:80 tcp:80` on your PC, or\n" +
                "• Same Wi‑Fi: add dev.api.host=YOUR_PC_IP to android-app/local.properties and rebuild."
        }
    }
}
