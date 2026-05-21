package com.bnhs.edutrack

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.bnhs.edutrack.auth.AuthSession
import com.bnhs.edutrack.backup.DatabaseBackupPanel
import com.bnhs.edutrack.securityaudit.SecurityAuditPanel
import com.bnhs.edutrack.transaction.TransactionIntegrityPanel
import com.bnhs.edutrack.tracking.SessionActivityPanel
import com.bnhs.edutrack.rbac.RbacAccessDenied
import com.bnhs.edutrack.rbac.RbacEnforcer
import com.bnhs.edutrack.rbac.RbacHierarchyPanel
import com.bnhs.edutrack.rbac.RbacPermission
import com.bnhs.edutrack.records.RecordsModuleScreen
import com.bnhs.edutrack.records.RecordsViewModel

private val PrimaryDark = Color(0xFF1E1B4B)
private val PrimaryMain = Color(0xFF4338CA)
private val SecondaryMain = Color(0xFF06B6D4)
private val SuccessMain = Color(0xFF10B981)
private val ErrorMain = Color(0xFFF43F5E)
private val TextSubtitle = Color(0xFF64748B)

@Composable
fun AdminDashboard(
    tab: AdminTab,
    session: AuthSession,
    rbac: RbacEnforcer,
    recordsViewModel: RecordsViewModel,
    students: List<Student>,
    records: List<AttendanceRecord>,
    statusMessage: (String) -> Unit,
    onDataRestored: () -> Unit = {},
) {
    Column(modifier = Modifier.fillMaxSize()) {
        Text("Administrator", color = PrimaryDark, fontWeight = FontWeight.Black, fontSize = 16.sp)
        Text("School-wide monitoring — not for gate scans or class marking.", fontSize = 12.sp, color = TextSubtitle)
        Spacer(modifier = Modifier.height(8.dp))

        Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
            when (tab) {
                AdminTab.OVERVIEW -> AdminOverviewTab(students, records, statusMessage)
                AdminTab.RECORDS -> if (rbac.canManageStudentRecords()) {
                    RecordsModuleScreen(recordsViewModel, rbac, Modifier.fillMaxSize())
                } else {
                    RbacAccessDenied(RbacPermission.RECORDS_MANAGE, rbac, Modifier.fillMaxSize())
                }
                AdminTab.SYSTEM -> AdminRbacSystemTab(
                    session = session,
                    rbac = rbac,
                    records = records,
                    recordsViewModel = recordsViewModel,
                    statusMessage = statusMessage,
                    onDataRestored = onDataRestored,
                    modifier = Modifier.fillMaxSize(),
                )
            }
        }
    }
}

@Composable
private fun AdminOverviewTab(
    students: List<Student>,
    records: List<AttendanceRecord>,
    statusMessage: (String) -> Unit,
) {
    val today = records.count { it.loggedBy == "GATE" }
    val adviserMarks = records.count { it.loggedBy == "ADVISER" || it.loggedBy == "TEACHER" }
    val absentAlerts = students.count { s ->
        records.any { it.studentId == s.id && it.status == "ABSENT" && it.loggedBy != "GATE" }
    }

    LaunchedEffect(Unit) { statusMessage("Admin overview loaded.") }

    Column(Modifier.verticalScroll(rememberScrollState()), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Row(horizontalArrangement = Arrangement.spacedBy(10.dp), modifier = Modifier.fillMaxWidth()) {
            StatCard("Enrolled (demo)", students.size.toString(), Icons.Default.Groups, PrimaryMain, Modifier.weight(1f))
            StatCard("Gate scans", today.toString(), Icons.Default.Security, SecondaryMain, Modifier.weight(1f))
        }
        Row(horizontalArrangement = Arrangement.spacedBy(10.dp), modifier = Modifier.fillMaxWidth()) {
            StatCard("Adviser marks", adviserMarks.toString(), Icons.Default.FactCheck, SuccessMain, Modifier.weight(1f))
            StatCard("Open concerns", absentAlerts.toString(), Icons.Default.WarningAmber, ErrorMain, Modifier.weight(1f))
        }
        Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
            Column(Modifier.padding(16.dp)) {
                Text("Admin actions", fontWeight = FontWeight.Bold, color = PrimaryDark)
                Spacer(modifier = Modifier.height(8.dp))
                Text("• Manage student records — CRUD, search, filter (Records tab)", fontSize = 13.sp, color = TextSubtitle)
                Text("• Check SMS & session health (System tab)", fontSize = 13.sp, color = TextSubtitle)
                Text("• Gate & class attendance are handled by Security and Adviser accounts only.", fontSize = 13.sp, color = TextSubtitle)
            }
        }
    }
}

@Composable
private fun AdminRbacSystemTab(
    session: AuthSession,
    rbac: RbacEnforcer,
    records: List<AttendanceRecord>,
    recordsViewModel: RecordsViewModel,
    statusMessage: (String) -> Unit,
    onDataRestored: () -> Unit = {},
    modifier: Modifier = Modifier,
) {
    LaunchedEffect(Unit) { statusMessage("RBAC & system panel ready.") }

    Column(
        modifier = modifier
            .verticalScroll(rememberScrollState())
            .padding(bottom = 16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Text("RBAC & System", fontWeight = FontWeight.Black, color = PrimaryDark, fontSize = 15.sp)
        Text(
            "Role hierarchy, your permissions, and device session info.",
            fontSize = 12.sp,
            color = TextSubtitle,
        )

        RbacHierarchyPanel(rbac = rbac)

        SessionActivityPanel()

        SecurityAuditPanel(onStatus = statusMessage)

        TransactionIntegrityPanel(onStatus = statusMessage)

        DatabaseBackupPanel(
            actorEmail = session.user.email,
            onStatus = statusMessage,
            onRestoreSuccess = onDataRestored,
        )

        Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color(0xFFE8F4FD))) {
            Column(Modifier.padding(14.dp)) {
                Text("Data security (schema v7)", fontWeight = FontWeight.Bold, color = PrimaryDark)
                Text("• Business transactions: ACID commit/rollback log", fontSize = 12.sp, color = TextSubtitle)
                Text("• Security incidents & audit reports: encrypted at rest", fontSize = 12.sp, color = TextSubtitle)
                Text("• Activity logs: AES-256-GCM encrypted email & details", fontSize = 12.sp, color = TextSubtitle)
                Text("• Parent contacts & SMS numbers: AES-256-GCM encrypted at rest", fontSize = 12.sp, color = TextSubtitle)
                Text("• Local staff passwords: salted SHA-256 (never plaintext)", fontSize = 12.sp, color = TextSubtitle)
                Text("• API session token: EncryptedSharedPreferences", fontSize = 12.sp, color = TextSubtitle)
                Text("• Unique indexes on LRN, RFID, username", fontSize = 12.sp, color = TextSubtitle)
            }
        }

        Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
            Column(Modifier.padding(16.dp)) {
                Text("Signed-in session", fontWeight = FontWeight.Bold, color = PrimaryDark)
                Spacer(modifier = Modifier.height(8.dp))
                Text("User: ${session.user.name}", fontSize = 13.sp)
                Text("Email: ${session.user.email}", fontSize = 13.sp, color = TextSubtitle)
                Text("Roles: ${session.user.roles.joinToString().ifBlank { "none" }}", fontSize = 13.sp, color = TextSubtitle)
                Text(
                    "API permissions: ${session.user.permissions.joinToString().ifBlank { "(using role fallback)" }}",
                    fontSize = 12.sp,
                    color = TextSubtitle,
                )
            }
        }

        if (rbac.canManageUsers()) {
            Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color(0xFFEEF2FF))) {
                Column(Modifier.padding(14.dp)) {
                    Text("User management", fontWeight = FontWeight.Bold, color = PrimaryDark)
                    Text(
                        "Admins with users.manage can add users on the web LMS.",
                        fontSize = 12.sp,
                        color = TextSubtitle,
                    )
                }
            }
        }

        Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
            Column(Modifier.padding(16.dp)) {
                Text("Attendance cache", fontWeight = FontWeight.Bold, color = PrimaryDark)
                Spacer(modifier = Modifier.height(8.dp))
                Text("${records.size} local rows (demo device storage)", fontSize = 13.sp, color = TextSubtitle)
                Text("Gate entries: ${records.count { it.loggedBy == "GATE" }}", fontSize = 13.sp)
                Text(
                    "Adviser entries: ${records.count { it.loggedBy == "ADVISER" || it.loggedBy == "TEACHER" }}",
                    fontSize = 13.sp,
                )
            }
        }

        Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color(0xFFFFF7ED))) {
            Column(Modifier.padding(16.dp)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.Sms, null, tint = SecondaryMain)
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("SMS gateway", fontWeight = FontWeight.Bold, color = PrimaryDark)
                }
                Spacer(modifier = Modifier.height(6.dp))
                Text(
                    "Parent SMS alerts are configured on the Laravel server (Twilio). This admin view does not send SMS directly.",
                    fontSize = 12.sp,
                    color = TextSubtitle,
                    lineHeight = 16.sp,
                )
            }
        }

        Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
            Column(Modifier.padding(16.dp)) {
                Text("Records audit trail", fontWeight = FontWeight.Bold, color = PrimaryDark)
                Spacer(modifier = Modifier.height(6.dp))
                Text(recordsViewModel.statusMessage, fontSize = 12.sp, color = TextSubtitle)
                Text(
                    "Create/update/delete actions are logged with your signed-in email.",
                    fontSize = 12.sp,
                    color = TextSubtitle,
                )
            }
        }
    }
}

@Composable
fun AdminBottomNav(selected: AdminTab, rbac: RbacEnforcer, onSelect: (AdminTab) -> Unit) {
    val items = buildList {
        if (rbac.canViewDashboard()) {
            add(Triple(AdminTab.OVERVIEW, Icons.Default.Dashboard, "Overview"))
        }
        if (rbac.canManageStudentRecords()) {
            add(Triple(AdminTab.RECORDS, Icons.Default.FolderShared, "Records"))
        }
        add(Triple(AdminTab.SYSTEM, Icons.Default.Shield, "RBAC"))
    }
    if (items.isNotEmpty()) {
        DashboardBottomNav(items = items, selected = selected, onSelect = onSelect)
    }
}

@Composable
private fun StatCard(label: String, value: String, icon: ImageVector, tint: Color, modifier: Modifier = Modifier) {
    Card(modifier = modifier, shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Column(Modifier.padding(14.dp)) {
            Icon(icon, null, tint = tint, modifier = Modifier.size(22.dp))
            Spacer(modifier = Modifier.height(8.dp))
            Text(value, fontWeight = FontWeight.Black, fontSize = 22.sp, color = PrimaryDark)
            Text(label, fontSize = 11.sp, color = TextSubtitle)
        }
    }
}
