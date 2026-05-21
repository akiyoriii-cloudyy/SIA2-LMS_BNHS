package com.bnhs.edutrack.securityaudit

import com.bnhs.edutrack.tracking.ActivityAction
import com.bnhs.edutrack.tracking.ActivityCategory
import com.bnhs.edutrack.tracking.ActivityLogView

/**
 * Rule-based intrusion detection over decrypted activity logs (Member 2 lab).
 */
object IntrusionDetector {

    private const val WINDOW_15_MIN = 15 * 60 * 1000L
    private const val WINDOW_30_MIN = 30 * 60 * 1000L
    private const val WINDOW_60_MIN = 60 * 60 * 1000L

    data class Detection(
        val incidentType: String,
        val severity: String,
        val description: String,
        val actorEmail: String,
    )

    fun analyze(
        logs: List<ActivityLogView>,
        latestCategory: String,
        latestAction: String,
        latestSuccess: Boolean,
        latestEmail: String,
        latestDetails: String,
    ): List<Detection> {
        val now = System.currentTimeMillis()
        val findings = mutableListOf<Detection>()

        if (latestCategory == ActivityCategory.AUTH && latestAction == ActivityAction.LOGIN_FAILURE) {
            val email = latestEmail.ifBlank { "unknown" }
            val recentFailures = logs.filter {
                it.category == ActivityCategory.AUTH &&
                    it.action == ActivityAction.LOGIN_FAILURE &&
                    it.createdAt >= now - WINDOW_15_MIN &&
                    (email == "unknown" || it.actorEmail.equals(email, ignoreCase = true))
            }
            if (recentFailures.size >= 3) {
                findings.add(
                    Detection(
                        incidentType = IncidentType.BRUTE_FORCE,
                        severity = Severity.HIGH,
                        description = "$email: ${recentFailures.size} failed login attempts in 15 minutes.",
                        actorEmail = email,
                    ),
                )
            }

            val hourFailures = logs.count {
                it.category == ActivityCategory.AUTH &&
                    it.action == ActivityAction.LOGIN_FAILURE &&
                    it.createdAt >= now - WINDOW_60_MIN
            }
            if (hourFailures >= 5) {
                findings.add(
                    Detection(
                        incidentType = IncidentType.EXCESSIVE_LOGIN_FAILURES,
                        severity = Severity.MEDIUM,
                        description = "$hourFailures failed login attempts in the last hour.",
                        actorEmail = email,
                    ),
                )
            }

            if (latestDetails.contains("Access denied", ignoreCase = true)) {
                findings.add(
                    Detection(
                        incidentType = IncidentType.ACCESS_DENIED,
                        severity = Severity.MEDIUM,
                        description = "Portal access denied for $email.",
                        actorEmail = email,
                    ),
                )
            }
        }

        if (latestCategory == ActivityCategory.ACCOUNT &&
            latestAction == ActivityAction.PASSWORD_RESET_REQUEST
        ) {
            val email = latestEmail.ifBlank { "unknown" }
            val resets = logs.count {
                it.category == ActivityCategory.ACCOUNT &&
                    it.action == ActivityAction.PASSWORD_RESET_REQUEST &&
                    it.createdAt >= now - WINDOW_30_MIN &&
                    (email == "unknown" || it.actorEmail.equals(email, ignoreCase = true))
            }
            if (resets >= 3) {
                findings.add(
                    Detection(
                        incidentType = IncidentType.SUSPICIOUS_PASSWORD_RESET,
                        severity = Severity.MEDIUM,
                        description = "$email: $resets password reset requests in 30 minutes.",
                        actorEmail = email,
                    ),
                )
            }
        }

        if (latestCategory == ActivityCategory.SYSTEM &&
            latestAction == ActivityAction.DATABASE_RESTORE
        ) {
            findings.add(
                Detection(
                    incidentType = IncidentType.UNAUTHORIZED_RESTORE,
                    severity = Severity.HIGH,
                    description = "Database restore executed by ${latestEmail.ifBlank { "unknown" }}. Verify authorization.",
                    actorEmail = latestEmail,
                ),
            )
        }

        return findings.distinctBy { "${it.incidentType}:${it.actorEmail}" }
    }

    fun computeRiskLevel(failedLogins24h: Int, openIncidents: Int, incidents24h: Int): String = when {
        openIncidents >= 3 || failedLogins24h >= 10 || incidents24h >= 5 -> RiskLevel.RED
        openIncidents >= 1 || failedLogins24h >= 3 || incidents24h >= 2 -> RiskLevel.YELLOW
        else -> RiskLevel.GREEN
    }
}
