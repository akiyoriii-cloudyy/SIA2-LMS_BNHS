package com.bnhs.edutrack.securityaudit

import android.content.Context
import com.bnhs.edutrack.data.BnhsDatabase
import com.bnhs.edutrack.security.SensitiveDataGuard
import com.bnhs.edutrack.tracking.ActivityAction
import com.bnhs.edutrack.tracking.ActivityCategory
import com.bnhs.edutrack.tracking.ActivityLogger
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.concurrent.TimeUnit

/**
 * Security auditing, intrusion detection, and periodic reports (Member 2 lab).
 */
class SecurityAuditService private constructor(
    private val context: Context,
    private val db: BnhsDatabase,
    private val guard: SensitiveDataGuard,
) {

    private val activityLogger = ActivityLogger.get(context)

    suspend fun onActivityLogged(
        category: String,
        action: String,
        success: Boolean,
        actorEmail: String?,
        details: String,
    ) {
        val logs = activityLogger.recent(120)
        val email = actorEmail.orEmpty()
        val detections = IntrusionDetector.analyze(
            logs = logs,
            latestCategory = category,
            latestAction = action,
            latestSuccess = success,
            latestEmail = email,
            latestDetails = details,
        )
        detections.forEach { recordIncident(it) }
    }

    private suspend fun recordIncident(detection: IntrusionDetector.Detection) {
        val recentSame = db.securityIncidentDao().recent(20).any {
            it.incidentType == detection.incidentType &&
                guard.decryptField(it.actorEmailEnc).equals(detection.actorEmail, ignoreCase = true) &&
                System.currentTimeMillis() - it.detectedAt < TimeUnit.MINUTES.toMillis(10)
        }
        if (recentSame) return

        val entity = SecurityIncidentEntity(
            incidentType = detection.incidentType,
            severity = detection.severity,
            descriptionEnc = guard.encryptField(detection.description),
            actorEmailEnc = guard.encryptField(detection.actorEmail),
        )
        val id = db.securityIncidentDao().insert(entity)
        val view = SecurityIncidentView(
            id = id,
            incidentType = detection.incidentType,
            severity = detection.severity,
            description = detection.description,
            actorEmail = detection.actorEmail,
            detectedAt = entity.detectedAt,
            acknowledged = false,
        )
        SecurityAlertNotifier.notifyIncident(context, view)

        activityLogger.log(
            category = ActivityCategory.SYSTEM,
            action = ActivityAction.SECURITY_BREACH,
            success = false,
            actorEmail = detection.actorEmail,
            details = "${detection.incidentType}: ${detection.description}",
        )
    }

    suspend fun currentStatus(): SecurityStatusSummary {
        val now = System.currentTimeMillis()
        val since24h = now - TimeUnit.HOURS.toMillis(24)
        val logs = loadLogsSince(since24h)
        val failedLogins24h = logs.count {
            it.category == ActivityCategory.AUTH && it.action == ActivityAction.LOGIN_FAILURE
        }
        val incidents24h = db.securityIncidentDao().since(since24h).size
        val openIncidents = db.securityIncidentDao().unacknowledged().size
        val lastReport = db.securityAuditReportDao().latest()

        return SecurityStatusSummary(
            riskLevel = IntrusionDetector.computeRiskLevel(failedLogins24h, openIncidents, incidents24h),
            openIncidents = openIncidents,
            failedLogins24h = failedLogins24h,
            incidents24h = incidents24h,
            lastReportAt = lastReport?.generatedAt,
        )
    }

    suspend fun generateReport(period: String): SecurityAuditReportView {
        val hours = when (period) {
            ReportPeriod.LAST_7D -> 24 * 7
            else -> 24
        }
        val since = System.currentTimeMillis() - TimeUnit.HOURS.toMillis(hours.toLong())
        val logs = loadLogsSince(since)
        val incidents = db.securityIncidentDao().since(since)
        val failedLogins = logs.count {
            it.category == ActivityCategory.AUTH && it.action == ActivityAction.LOGIN_FAILURE
        }
        val successLogins = logs.count {
            it.category == ActivityCategory.AUTH && it.action == ActivityAction.LOGIN_SUCCESS
        }
        val openIncidents = db.securityIncidentDao().unacknowledged().size
        val risk = IntrusionDetector.computeRiskLevel(
            failedLogins24h = if (period == ReportPeriod.LAST_24H) failedLogins else failedLogins,
            openIncidents = openIncidents,
            incidents24h = incidents.size,
        )

        val fmt = SimpleDateFormat("MMM d, yyyy HH:mm", Locale.getDefault())
        val summary = buildString {
            appendLine("BNHS EduTrack Security Audit Report")
            appendLine("Period: $period (since ${fmt.format(Date(since))})")
            appendLine("Risk level: $risk")
            appendLine("Failed login attempts: $failedLogins")
            appendLine("Successful logins: $successLogins")
            appendLine("Security incidents: ${incidents.size}")
            appendLine("Open (unacknowledged) incidents: $openIncidents")
            appendLine()
            appendLine("Recommendations:")
            when (risk) {
                RiskLevel.RED -> appendLine("• Investigate failed logins immediately; consider locking affected accounts on the server.")
                RiskLevel.YELLOW -> appendLine("• Review incident list and confirm all admin actions are authorized.")
                else -> appendLine("• No critical threats detected. Continue routine monitoring.")
            }
        }

        val entity = SecurityAuditReportEntity(
            periodLabel = period,
            riskLevel = risk,
            summaryEnc = guard.encryptField(summary),
            failedLoginCount = failedLogins,
            incidentCount = incidents.size,
            successfulLoginCount = successLogins,
        )
        val id = db.securityAuditReportDao().insert(entity)
        val view = SecurityAuditReportView(
            id = id,
            periodLabel = period,
            riskLevel = risk,
            summary = summary,
            failedLoginCount = failedLogins,
            incidentCount = incidents.size,
            successfulLoginCount = successLogins,
            generatedAt = entity.generatedAt,
        )
        SecurityAlertNotifier.notifyAuditReport(context, view)
        return view
    }

    suspend fun recentIncidents(limit: Int = 15): List<SecurityIncidentView> =
        db.securityIncidentDao().recent(limit).map { toIncidentView(it) }

    suspend fun recentReports(limit: Int = 5): List<SecurityAuditReportView> =
        db.securityAuditReportDao().recent(limit).map { toReportView(it) }

    suspend fun acknowledgeIncident(id: Long) {
        db.securityIncidentDao().acknowledge(id)
    }

    suspend fun maybeGeneratePeriodicReports() {
        val last = db.securityAuditReportDao().latest()?.generatedAt ?: 0L
        val dayAgo = System.currentTimeMillis() - TimeUnit.HOURS.toMillis(24)
        if (last < dayAgo) {
            generateReport(ReportPeriod.LAST_24H)
        }
    }

    private suspend fun loadLogsSince(since: Long) =
        db.activityLogDao().since(since).map { row ->
            com.bnhs.edutrack.tracking.ActivityLogView(
                id = row.id,
                category = row.category,
                action = row.action,
                success = row.success,
                actorEmail = guard.decryptField(row.actorEmailEnc),
                details = guard.decryptField(row.detailsEnc),
                createdAt = row.createdAt,
                sessionUuid = row.sessionUuid,
            )
        }

    private fun toIncidentView(row: SecurityIncidentEntity) = SecurityIncidentView(
        id = row.id,
        incidentType = row.incidentType,
        severity = row.severity,
        description = guard.decryptField(row.descriptionEnc),
        actorEmail = guard.decryptField(row.actorEmailEnc),
        detectedAt = row.detectedAt,
        acknowledged = row.acknowledged,
    )

    private fun toReportView(row: SecurityAuditReportEntity) = SecurityAuditReportView(
        id = row.id,
        periodLabel = row.periodLabel,
        riskLevel = row.riskLevel,
        summary = guard.decryptField(row.summaryEnc),
        failedLoginCount = row.failedLoginCount,
        incidentCount = row.incidentCount,
        successfulLoginCount = row.successfulLoginCount,
        generatedAt = row.generatedAt,
    )

    companion object {
        @Volatile
        private var instance: SecurityAuditService? = null

        fun get(context: Context): SecurityAuditService =
            instance ?: synchronized(this) {
                instance ?: SecurityAuditService(
                    context.applicationContext,
                    BnhsDatabase.get(context),
                    SensitiveDataGuard.get(context),
                ).also { instance = it }
            }
    }
}
