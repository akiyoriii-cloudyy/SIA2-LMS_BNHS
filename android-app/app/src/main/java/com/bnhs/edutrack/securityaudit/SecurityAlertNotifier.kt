package com.bnhs.edutrack.securityaudit

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.content.Context
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.ActivityCompat
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import java.util.concurrent.atomic.AtomicInteger

object SecurityAlertNotifier {

    const val CHANNEL_SECURITY = "edutrack_security_alerts"

    private val notificationId = AtomicInteger(7000)

    fun ensureChannel(context: Context) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
        val nm = context.getSystemService(NotificationManager::class.java) ?: return
        nm.createNotificationChannel(
            NotificationChannel(
                CHANNEL_SECURITY,
                "Security alerts",
                NotificationManager.IMPORTANCE_HIGH,
            ).apply {
                description = "Suspicious login activity and security incidents"
                enableVibration(true)
            },
        )
    }

    fun notifyIncident(context: Context, incident: SecurityIncidentView) {
        ensureChannel(context)
        if (!canPost(context)) return

        val title = when (incident.severity) {
            Severity.CRITICAL, Severity.HIGH -> "Security alert: ${incident.incidentType}"
            else -> "Security notice"
        }
        val builder = NotificationCompat.Builder(context, CHANNEL_SECURITY)
            .setSmallIcon(android.R.drawable.ic_dialog_alert)
            .setContentTitle(title)
            .setContentText(incident.description.take(120))
            .setStyle(NotificationCompat.BigTextStyle().bigText(incident.description))
            .setPriority(
                if (incident.severity == Severity.HIGH || incident.severity == Severity.CRITICAL) {
                    NotificationCompat.PRIORITY_HIGH
                } else {
                    NotificationCompat.PRIORITY_DEFAULT
                },
            )
            .setAutoCancel(true)

        NotificationManagerCompat.from(context).notify(notificationId.incrementAndGet(), builder.build())
    }

    fun notifyAuditReport(context: Context, report: SecurityAuditReportView) {
        ensureChannel(context)
        if (!canPost(context)) return

        val builder = NotificationCompat.Builder(context, CHANNEL_SECURITY)
            .setSmallIcon(android.R.drawable.ic_menu_report_image)
            .setContentTitle("Security audit report ready")
            .setContentText("${report.periodLabel}: risk ${report.riskLevel} · ${report.failedLoginCount} failed logins")
            .setStyle(NotificationCompat.BigTextStyle().bigText(report.summary.take(300)))
            .setPriority(NotificationCompat.PRIORITY_DEFAULT)
            .setAutoCancel(true)

        NotificationManagerCompat.from(context).notify(notificationId.incrementAndGet(), builder.build())
    }

    private fun canPost(context: Context): Boolean =
        Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU ||
            ActivityCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) ==
            PackageManager.PERMISSION_GRANTED
}
