package com.bnhs.edutrack.securityaudit

import com.bnhs.edutrack.ui.*

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Report
import androidx.compose.material.icons.filled.Security
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

private val WarningMain = Color(0xFFF59E0B)

@Composable
fun SecurityAuditPanel(
    modifier: Modifier = Modifier,
    onStatus: (String) -> Unit = {},
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val auditService = remember { SecurityAuditService.get(context) }

    var status by remember { mutableStateOf<SecurityStatusSummary?>(null) }
    var incidents by remember { mutableStateOf<List<SecurityIncidentView>>(emptyList()) }
    var reports by remember { mutableStateOf<List<SecurityAuditReportView>>(emptyList()) }
    var loading by remember { mutableStateOf(true) }

    fun refresh() {
        scope.launch {
            loading = true
            auditService.maybeGeneratePeriodicReports()
            status = auditService.currentStatus()
            incidents = auditService.recentIncidents(10)
            reports = auditService.recentReports(3)
            loading = false
        }
    }

    LaunchedEffect(Unit) { refresh() }

    val riskColor = when (status?.riskLevel) {
        RiskLevel.GREEN -> SuccessMain
        RiskLevel.YELLOW -> WarningMain
        RiskLevel.RED -> ErrorMain
        else -> TextSubtitle
    }

    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = Color(0xFFFFF1F2)),
    ) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Row(verticalAlignment = androidx.compose.ui.Alignment.CenterVertically) {
                Icon(Icons.Default.Security, null, tint = ErrorMain)
                Spacer(Modifier.width(8.dp))
                Text("Security audit & intrusion detection", fontWeight = FontWeight.Bold, color = PrimaryDark)
            }
            Text(
                "Failed logins, breaches, and suspicious activity trigger alerts. Reports are encrypted at rest.",
                fontSize = 12.sp,
                color = TextSubtitle,
                lineHeight = 16.sp,
            )

            if (loading) {
                LinearProgressIndicator(Modifier.fillMaxWidth())
            } else if (status != null) {
                val s = status!!
                Card(colors = CardDefaults.cardColors(containerColor = Color.White)) {
                    Column(Modifier.padding(12.dp)) {
                        Text("System security status", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                        Text(
                            "Risk level: ${s.riskLevel}",
                            color = riskColor,
                            fontWeight = FontWeight.Black,
                            fontSize = 18.sp,
                        )
                        Text("Failed logins (24h): ${s.failedLogins24h}", fontSize = 12.sp, color = TextSubtitle)
                        Text("Incidents (24h): ${s.incidents24h} · Open: ${s.openIncidents}", fontSize = 12.sp, color = TextSubtitle)
                        s.lastReportAt?.let { t ->
                            val fmt = SimpleDateFormat("MMM d HH:mm", Locale.getDefault())
                            Text("Last report: ${fmt.format(Date(t))}", fontSize = 11.sp, color = TextSubtitle)
                        }
                    }
                }
            }

            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(
                    onClick = {
                        scope.launch {
                            auditService.generateReport(ReportPeriod.LAST_24H)
                            onStatus("24-hour security report generated.")
                            refresh()
                        }
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = PrimaryDark),
                ) {
                    Icon(Icons.Default.Report, null, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(4.dp))
                    Text("Report 24h", fontSize = 12.sp)
                }
                OutlinedButton(
                    onClick = {
                        scope.launch {
                            auditService.generateReport(ReportPeriod.LAST_7D)
                            onStatus("7-day security report generated.")
                            refresh()
                        }
                    },
                ) {
                    Text("Report 7d", fontSize = 12.sp)
                }
            }

            if (incidents.isNotEmpty()) {
                Text("Recent incidents", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                incidents.take(5).forEach { inc ->
                    val fmt = SimpleDateFormat("MMM d HH:mm", Locale.getDefault())
                    Row(
                        Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = androidx.compose.ui.Alignment.CenterVertically,
                    ) {
                        Column(Modifier.weight(1f)) {
                            Row(verticalAlignment = androidx.compose.ui.Alignment.CenterVertically) {
                                Icon(Icons.Default.Warning, null, tint = ErrorMain, modifier = Modifier.size(14.dp))
                                Spacer(Modifier.width(4.dp))
                                Text("${inc.incidentType} (${inc.severity})", fontSize = 11.sp, fontWeight = FontWeight.Bold)
                            }
                            Text(inc.description.take(80), fontSize = 10.sp, color = TextSubtitle, maxLines = 2)
                            Text(fmt.format(Date(inc.detectedAt)), fontSize = 9.sp, color = TextSubtitle)
                        }
                        if (!inc.acknowledged) {
                            TextButton(
                                onClick = {
                                    scope.launch {
                                        auditService.acknowledgeIncident(inc.id)
                                        refresh()
                                    }
                                },
                            ) { Text("Ack", fontSize = 10.sp) }
                        }
                    }
                }
            }

            reports.firstOrNull()?.let { r ->
                Text("Latest audit summary", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                Text(r.summary.lines().take(6).joinToString("\n"), fontSize = 10.sp, color = TextSubtitle, lineHeight = 14.sp)
            }

            TextButton(onClick = { refresh() }) { Text("Refresh security status") }
        }
    }
}
