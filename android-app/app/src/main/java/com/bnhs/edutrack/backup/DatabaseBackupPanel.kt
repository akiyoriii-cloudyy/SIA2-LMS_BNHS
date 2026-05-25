package com.bnhs.edutrack.backup

import com.bnhs.edutrack.ui.*

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Backup
import androidx.compose.material.icons.filled.Restore
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.bnhs.edutrack.data.BackupMetaEntity
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale


@Composable
fun DatabaseBackupPanel(
    actorEmail: String,
    onStatus: (String) -> Unit,
    onRestoreSuccess: () -> Unit = {},
    modifier: Modifier = Modifier,
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val backupManager = remember { DatabaseBackupManager.get(context) }
    val restoreManager = remember { DatabaseRestoreManager.get(context) }
    var backups by remember { mutableStateOf<List<BackupMetaEntity>>(emptyList()) }
    var busy by remember { mutableStateOf(false) }
    var showRestoreConfirm by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        backups = backupManager.listBackups()
    }

    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = Color(0xFFF0FDF4)),
    ) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Row(verticalAlignment = androidx.compose.ui.Alignment.CenterVertically) {
                Icon(Icons.Default.Backup, null, tint = SuccessMain)
                Spacer(Modifier.width(8.dp))
                Text("Database backup & restore", fontWeight = FontWeight.Bold, color = PrimaryDark)
            }
            Text(
                "Encrypted PII in SQLite · JSON snapshots saved to app storage for disaster recovery.",
                fontSize = 12.sp,
                color = TextSubtitle,
                lineHeight = 16.sp,
            )
            Text(
                "Folder: ${backupManager.backupDirectory().absolutePath}",
                fontSize = 10.sp,
                color = TextSubtitle,
            )

            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(
                    onClick = {
                        if (busy) return@Button
                        busy = true
                        scope.launch {
                            backupManager.createBackup(actorEmail)
                                .onSuccess { meta ->
                                    backups = backupManager.listBackups()
                                    onStatus("Backup saved: ${meta.fileName} (${meta.recordCount} records)")
                                }
                                .onFailure { onStatus("Backup failed: ${it.message}") }
                            busy = false
                        }
                    },
                    enabled = !busy,
                    colors = ButtonDefaults.buttonColors(containerColor = PrimaryMain),
                ) {
                    Icon(Icons.Default.Backup, null, modifier = Modifier.size(18.dp))
                    Spacer(Modifier.width(6.dp))
                    Text("Create backup")
                }
                OutlinedButton(
                    onClick = { showRestoreConfirm = true },
                    enabled = !busy && backups.isNotEmpty(),
                ) {
                    Icon(Icons.Default.Restore, null, modifier = Modifier.size(18.dp))
                    Spacer(Modifier.width(6.dp))
                    Text("Restore latest")
                }
            }

            if (backups.isEmpty()) {
                Text("No backups yet. Tap Create backup before restoring.", fontSize = 12.sp, color = TextSubtitle)
            } else {
                Text("Recent backups", fontWeight = FontWeight.SemiBold, fontSize = 13.sp, color = PrimaryDark)
                backups.take(3).forEach { meta ->
                    val whenStr = SimpleDateFormat("MMM d, yyyy HH:mm", Locale.getDefault()).format(Date(meta.createdAt))
                    Text(
                        "• ${meta.fileName} — ${meta.recordCount} rows ($whenStr)",
                        fontSize = 11.sp,
                        color = TextSubtitle,
                    )
                }
            }
        }
    }

    if (showRestoreConfirm) {
        AlertDialog(
            onDismissRequest = { showRestoreConfirm = false },
            title = { Text("Restore database?") },
            text = {
                Text(
                    "This replaces all local students, parents, attendance, and accounts with the latest backup. Continue?",
                    color = TextSubtitle,
                )
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        showRestoreConfirm = false
                        if (busy) return@TextButton
                        busy = true
                        scope.launch {
                            restoreManager.restoreLatest(actorEmail)
                                .onSuccess { count ->
                                    onStatus("Restore complete ($count records).")
                                    onRestoreSuccess()
                                }
                                .onFailure { onStatus("Restore failed: ${it.message}") }
                            busy = false
                        }
                    },
                ) { Text("Restore", color = ErrorMain, fontWeight = FontWeight.Bold) }
            },
            dismissButton = {
                TextButton(onClick = { showRestoreConfirm = false }) { Text("Cancel") }
            },
        )
    }
}
