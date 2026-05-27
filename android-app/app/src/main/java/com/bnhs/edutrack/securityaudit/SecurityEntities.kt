package com.bnhs.edutrack.securityaudit

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey

/** Recorded security incident / intrusion detection hit. */
@Entity(
    tableName = "security_incidents",
    indices = [Index(value = ["incident_type"]), Index(value = ["detected_at"])],
)
data class SecurityIncidentEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "incident_type") val incidentType: String,
    @ColumnInfo(name = "severity") val severity: String,
    @ColumnInfo(name = "description_enc") val descriptionEnc: String,
    @ColumnInfo(name = "actor_email_enc") val actorEmailEnc: String = "",
    @ColumnInfo(name = "detected_at") val detectedAt: Long = System.currentTimeMillis(),
    @ColumnInfo(name = "acknowledged") val acknowledged: Boolean = false,
)

/** Periodic audit report snapshot (Member 2 lab). */
@Entity(
    tableName = "security_audit_reports",
    indices = [Index(value = ["generated_at"])],
)
data class SecurityAuditReportEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "period_label") val periodLabel: String,
    @ColumnInfo(name = "risk_level") val riskLevel: String,
    @ColumnInfo(name = "summary_enc") val summaryEnc: String,
    @ColumnInfo(name = "failed_login_count") val failedLoginCount: Int,
    @ColumnInfo(name = "incident_count") val incidentCount: Int,
    @ColumnInfo(name = "successful_login_count") val successfulLoginCount: Int,
    @ColumnInfo(name = "generated_at") val generatedAt: Long = System.currentTimeMillis(),
)

data class SecurityIncidentView(
    val id: Long,
    val incidentType: String,
    val severity: String,
    val description: String,
    val actorEmail: String,
    val detectedAt: Long,
    val acknowledged: Boolean,
)

data class SecurityAuditReportView(
    val id: Long,
    val periodLabel: String,
    val riskLevel: String,
    val summary: String,
    val failedLoginCount: Int,
    val incidentCount: Int,
    val successfulLoginCount: Int,
    val generatedAt: Long,
)

data class SecurityStatusSummary(
    val riskLevel: String,
    val openIncidents: Int,
    val failedLogins24h: Int,
    val incidents24h: Int,
    val lastReportAt: Long?,
)
