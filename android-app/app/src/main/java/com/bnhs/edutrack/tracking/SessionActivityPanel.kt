package com.bnhs.edutrack.tracking

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Person
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

private val PrimaryDark = Color(0xFF1E1B4B)
private val PrimaryMain = Color(0xFF4338CA)
private val SuccessMain = Color(0xFF10B981)
private val ErrorMain = Color(0xFFF43F5E)
private val TextSubtitle = Color(0xFF64748B)

@Composable
fun SessionActivityPanel(modifier: Modifier = Modifier) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val sessionTracker = remember { SessionTracker.get(context) }
    val activityLogger = remember { ActivityLogger.get(context) }

    var activeSessions by remember { mutableStateOf<List<UserSessionView>>(emptyList()) }
    var activityLogs by remember { mutableStateOf<List<ActivityLogView>>(emptyList()) }
    var loading by remember { mutableStateOf(true) }

    fun refresh() {
        scope.launch {
            loading = true
            activeSessions = sessionTracker.activeSessions()
            activityLogs = activityLogger.recent(25)
            loading = false
        }
    }

    LaunchedEffect(Unit) { refresh() }

    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = Color(0xFFF5F3FF)),
    ) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Row(verticalAlignment = androidx.compose.ui.Alignment.CenterVertically) {
                Icon(Icons.Default.Person, null, tint = PrimaryMain)
                Spacer(Modifier.width(8.dp))
                Text("Session tracking & activity logs", fontWeight = FontWeight.Bold, color = PrimaryDark)
            }
            Text(
                "Active sessions and encrypted activity log (login attempts, account & record changes).",
                fontSize = 12.sp,
                color = TextSubtitle,
                lineHeight = 16.sp,
            )

            if (loading) {
                LinearProgressIndicator(Modifier.fillMaxWidth())
            } else {
                Text(
                    "Active sessions: ${activeSessions.size}",
                    fontWeight = FontWeight.SemiBold,
                    fontSize = 13.sp,
                    color = PrimaryDark,
                )
                activeSessions.take(3).forEach { s ->
                    val fmt = SimpleDateFormat("MMM d HH:mm", Locale.getDefault())
                    Text(
                        "• ${s.userName} (${s.userEmail}) — last active ${fmt.format(Date(s.lastActivityAt))}",
                        fontSize = 11.sp,
                        color = TextSubtitle,
                    )
                }

                HorizontalDivider(color = Color.LightGray.copy(alpha = 0.4f))

                Row(verticalAlignment = androidx.compose.ui.Alignment.CenterVertically) {
                    Icon(Icons.Default.History, null, tint = PrimaryMain, modifier = Modifier.size(18.dp))
                    Spacer(Modifier.width(6.dp))
                    Text("Recent activity (encrypted at rest)", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                }
                if (activityLogs.isEmpty()) {
                    Text("No activity logged yet.", fontSize = 12.sp, color = TextSubtitle)
                } else {
                    activityLogs.take(8).forEach { log ->
                        val time = SimpleDateFormat("MMM d HH:mm:ss", Locale.getDefault())
                            .format(Date(log.createdAt))
                        val statusColor = if (log.success) SuccessMain else ErrorMain
                        Text(
                            "$time · ${log.category}/${log.action} · ${if (log.success) "OK" else "FAIL"}",
                            fontSize = 10.sp,
                            color = statusColor,
                        )
                        Text(
                            log.details.take(120),
                            fontSize = 10.sp,
                            color = TextSubtitle,
                            maxLines = 2,
                        )
                    }
                }
            }

            TextButton(onClick = { refresh() }) {
                Text("Refresh logs")
            }
        }
    }
}
