package com.bnhs.edutrack

import android.Manifest
import android.app.DatePickerDialog
import android.app.NotificationChannel
import android.app.NotificationManager
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.os.VibrationEffect
import android.os.Vibrator
import android.content.pm.PackageManager
import android.provider.Settings
import android.media.AudioManager
import android.media.ToneGenerator
import android.telephony.SmsManager
import android.widget.Toast
import androidx.activity.ComponentActivity
import androidx.activity.compose.BackHandler
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.compose.setContent
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.border
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.focus.FocusRequester
import androidx.compose.ui.focus.focusRequester
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.app.ActivityCompat
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import java.time.LocalDate
import java.time.LocalDateTime
import java.time.LocalTime
import java.time.ZoneId
import java.time.ZonedDateTime
import java.time.format.DateTimeFormatter
import java.util.concurrent.atomic.AtomicInteger
import kotlin.jvm.Volatile
import kotlinx.coroutines.delay

// --- Header / BNHS brand palette ---
private val PrimaryDark = Color(0xFF1E1B4B) // Indigo 950
private val PrimaryMain = Color(0xFF4338CA) // Indigo 700
private val SecondaryMain = Color(0xFF06B6D4) // Cyan 500
private val AccentMain = Color(0xFFFACC15) // Yellow 400
private val SuccessMain = Color(0xFF10B981) // Emerald 500
private val ErrorMain = Color(0xFFF43F5E) // Rose 500
private val BgStart = Color(0xFFFDFDFD)
private val BgEnd = Color(0xFFF1F5F9)
private val TextSubtitle = Color(0xFF64748B)
private const val CHANNEL_ATTENDANCE = "edutrack_attendance"
private const val CHANNEL_PARENT_ALERT = "edutrack_parent_alerts"
private val PHILIPPINES_ZONE_ID: ZoneId = ZoneId.of("Asia/Manila")
private val notificationRequestId = AtomicInteger(3000)

private const val PARENT_ALERT_CONSECUTIVE_ABSENT_DAYS = 7

private fun nowInPhilippines(): ZonedDateTime = ZonedDateTime.now(PHILIPPINES_ZONE_ID)

private fun philippinesDateTimeForAttendanceDay(onAttendanceDate: LocalDate): LocalDateTime {
    val timeNow = LocalTime.now(PHILIPPINES_ZONE_ID)
    return LocalDateTime.of(onAttendanceDate, timeNow)
}

private fun philippinesStartOfDayUtcMillis(ld: LocalDate): Long =
    ld.atStartOfDay(PHILIPPINES_ZONE_ID).toInstant().toEpochMilli()

private fun philippinesInclusiveMaxPickerMillis(ld: LocalDate): Long =
    ld.plusDays(1).atStartOfDay(PHILIPPINES_ZONE_ID).toInstant().toEpochMilli() - 1L

@Volatile
private var attendanceDatePickerShowing = false

private fun ComponentActivity.promptAttendanceRecordingDatePicker(
    current: LocalDate,
    anchoredPhilippinesToday: LocalDate,
    onPicked: (LocalDate) -> Unit,
) {
    if (attendanceDatePickerShowing) return
    attendanceDatePickerShowing = true
    val minAllowed = anchoredPhilippinesToday.minusYears(2)
    val maxAllowed = anchoredPhilippinesToday.plusMonths(36)
    val dlg = DatePickerDialog(
        this,
        { _, y, mo, dom ->
            attendanceDatePickerShowing = false
            onPicked(LocalDate.of(y, mo + 1, dom))
        },
        current.year,
        current.monthValue - 1,
        current.dayOfMonth,
    )
    dlg.datePicker.minDate = philippinesStartOfDayUtcMillis(minAllowed)
    dlg.datePicker.maxDate = philippinesInclusiveMaxPickerMillis(maxAllowed)
    dlg.setOnDismissListener { attendanceDatePickerShowing = false }
    dlg.show()
}

private fun ensureNotificationChannels(context: Context) {
    if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
    val nm = context.getSystemService(NotificationManager::class.java) ?: return
    nm.createNotificationChannel(
        NotificationChannel(
            CHANNEL_ATTENDANCE,
            "Attendance",
            NotificationManager.IMPORTANCE_DEFAULT
        ).apply { description = "Confirmations when attendance is recorded" }
    )
    nm.createNotificationChannel(
        NotificationChannel(
            CHANNEL_PARENT_ALERT,
            "Parent alerts",
            NotificationManager.IMPORTANCE_HIGH
        ).apply { description = "When a learner is absent 7 consecutive calendar days" }
    )
}

private fun postAttendanceLoggedNotification(context: Context, studentName: String, detail: String) {
    ensureNotificationChannels(context)
    val id = notificationRequestId.incrementAndGet()
    val builder = NotificationCompat.Builder(context, CHANNEL_ATTENDANCE)
        .setSmallIcon(android.R.drawable.ic_dialog_info)
        .setContentTitle("Attendance recorded")
        .setContentText("$studentName — $detail")
        .setStyle(NotificationCompat.BigTextStyle().bigText("$studentName — $detail"))
        .setPriority(NotificationCompat.PRIORITY_DEFAULT)
        .setAutoCancel(true)
    val nmCompat = NotificationManagerCompat.from(context)
    val notificationsOk =
        Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU ||
            ActivityCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) ==
            PackageManager.PERMISSION_GRANTED
    if (notificationsOk) nmCompat.notify(id, builder.build())
}

private fun normalizePhilippineSmsAddress(raw: String): String {
    val digitsOnly = raw.asSequence().filter { it.isDigit() }.joinToString("")
    if (digitsOnly.isEmpty()) return raw.trim()
    return when {
        digitsOnly.startsWith("63") && digitsOnly.length >= 11 -> "+" + digitsOnly
        digitsOnly.startsWith("09") -> "+63${digitsOnly.drop(1)}"
        digitsOnly.startsWith("9") && digitsOnly.length >= 9 -> "+63$digitsOnly"
        else -> raw.trim()
    }
}

private fun getSmsManagerCompat(context: Context): SmsManager {
    return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
        context.getSystemService(SmsManager::class.java) ?: run {
            @Suppress("DEPRECATION")
            SmsManager.getDefault()
        }
    } else {
        @Suppress("DEPRECATION")
        SmsManager.getDefault()
    }
}

private fun permissionsNeededForParentAlerts(context: Context): Array<String> {
    val needed = mutableListOf(Manifest.permission.SEND_SMS)
    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
        needed.add(Manifest.permission.POST_NOTIFICATIONS)
    }
    return needed.filter { perm ->
        ContextCompat.checkSelfPermission(context, perm) != PackageManager.PERMISSION_GRANTED
    }.toTypedArray()
}

private fun sendParentAbsentWeekSms(
    context: Context,
    parentContactRaw: String,
    body: String,
    onOutcome: ((String) -> Unit)?,
) {
    if (ActivityCompat.checkSelfPermission(context, Manifest.permission.SEND_SMS) != PackageManager.PERMISSION_GRANTED) {
        onOutcome?.invoke("SMS permission denied")
        return
    }
    val dest = normalizePhilippineSmsAddress(parentContactRaw)
    if (dest.isBlank()) {
        onOutcome?.invoke("Empty number")
        return
    }
    try {
        val mgr = getSmsManagerCompat(context)
        val segments = mgr.divideMessage(body)
        if (segments.size <= 1) {
            mgr.sendTextMessage(dest, null, body, null, null)
        } else {
            mgr.sendMultipartTextMessage(dest, null, segments, null, null)
        }
        onOutcome?.invoke("SMS sent")
    } catch (e: Exception) {
        onOutcome?.invoke("SMS failed ${e.localizedMessage}")
    }
}

private fun postParentAbsenceAlerts(
    context: Context,
    studentName: String,
    parentName: String,
    parentContactRaw: String,
    onStatusToast: ((String) -> Unit)?,
) {
    ensureNotificationChannels(context)
    val id = notificationRequestId.incrementAndGet()
    val smsBody =
        "Good day, $parentName. This is the adviser of $studentName from Bawing National High School. We respectfully inform you that your child has been absent for one week (${PARENT_ALERT_CONSECUTIVE_ABSENT_DAYS} consecutive days). Please visit the school for a conference. Thank you."
    val msg = "Formal parent notice prepared for $parentName regarding $studentName's one-week absence."
    val builder = NotificationCompat.Builder(context, CHANNEL_PARENT_ALERT)
        .setSmallIcon(android.R.drawable.ic_dialog_alert)
        .setContentTitle("Parent conference notice: 1-week absence")
        .setContentText(msg)
        .setPriority(NotificationCompat.PRIORITY_HIGH)
        .setAutoCancel(true)
    val nmCompat = NotificationManagerCompat.from(context)
    val notificationsOk =
        Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU ||
            ActivityCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) ==
            PackageManager.PERMISSION_GRANTED
    if (notificationsOk) nmCompat.notify(id, builder.build())

    sendParentAbsentWeekSms(context, parentContactRaw, smsBody) { smsResult ->
        onStatusToast?.invoke("Week-absent alerts: SMS $smsResult")
    }
}

private fun gateAccessFeedback(context: Context, granted: Boolean) {
    try {
        val vibrator = context.getSystemService(Vibrator::class.java) ?: return
        if (!vibrator.hasVibrator()) return
        val durationMs = if (granted) 60L else 220L
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            vibrator.vibrate(VibrationEffect.createOneShot(durationMs, VibrationEffect.DEFAULT_AMPLITUDE))
        } else {
            @Suppress("DEPRECATION")
            vibrator.vibrate(durationMs)
        }
    } catch (_: Exception) {}
    try {
        val gen = ToneGenerator(AudioManager.STREAM_NOTIFICATION, if (granted) 85 else 100)
        gen.startTone(if (granted) ToneGenerator.TONE_PROP_ACK else ToneGenerator.TONE_PROP_NACK, 180)
        Handler(Looper.getMainLooper()).postDelayed({ gen.release() }, 280L)
    } catch (_: Exception) {}
}

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        ensureNotificationChannels(this)
        setContent {
            MaterialTheme(
                colorScheme = lightColorScheme(
                    primary = PrimaryMain,
                    secondary = SecondaryMain,
                    surface = Color.White,
                    background = BgStart
                )
            ) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    AttendanceApp()
                }
            }
        }
    }
}

private data class Student(
    val id: Int,
    val name: String,
    val lrn: String,
    val rfidUid: String,
    val parentName: String,
    val parentContact: String
)

private data class AttendanceRecord(
    val studentId: Int,
    val date: LocalDate,
    val loggedAt: LocalDateTime,
    val status: String,
    val loggedBy: String
)

private fun upsertAttendanceRecord(
    records: MutableList<AttendanceRecord>,
    studentId: Int,
    date: LocalDate,
    loggedAt: LocalDateTime,
    status: String,
    loggedBy: String
) {
    records.removeAll { it.studentId == studentId && it.date == date }
    records.add(
        AttendanceRecord(
            studentId = studentId,
            date = date,
            loggedAt = loggedAt,
            status = status,
            loggedBy = loggedBy
        )
    )
}

private fun ensureDailyAbsenceDefaults(
    students: List<Student>,
    records: MutableList<AttendanceRecord>,
    date: LocalDate
) {
    val now = philippinesDateTimeForAttendanceDay(date)
    students.forEach { s ->
        val existing = decisiveRecordForDay(records, s.id, date)
        if (existing == null) {
            records.add(
                AttendanceRecord(
                    studentId = s.id,
                    date = date,
                    loggedAt = now,
                    status = "ABSENT",
                    loggedBy = "SYSTEM"
                )
            )
        }
    }
}

private fun decisiveRecordForDay(records: List<AttendanceRecord>, studentId: Int, date: LocalDate): AttendanceRecord? =
    records.filter { it.studentId == studentId && it.date == date }.maxByOrNull { it.loggedAt }

private fun consecutiveAbsentCalendarDaysEndingOn(
    records: List<AttendanceRecord>,
    studentId: Int,
    endOn: LocalDate,
): Int {
    var streak = 0
    var d = endOn
    while (true) {
        val rec = decisiveRecordForDay(records, studentId, d) ?: break
        if (rec.status.uppercase() == "ABSENT") {
            streak++
            d = d.minusDays(1)
        } else break
    }
    return streak
}

@Composable
private fun ParentAlertPermissionCard(
    context: Context,
    onRequestPermissions: () -> Unit,
    onOpenAppSettings: () -> Unit,
) {
    val activity = context as? ComponentActivity
    val smsOk = ContextCompat.checkSelfPermission(context, Manifest.permission.SEND_SMS) == PackageManager.PERMISSION_GRANTED
    val notifOk = Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU ||
            ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) == PackageManager.PERMISSION_GRANTED
    val allOk = smsOk && notifOk

    var expanded by remember { mutableStateOf(false) }

    OutlinedCard(
        modifier = Modifier.fillMaxWidth().animateContentSize(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.outlinedCardColors(containerColor = Color.White.copy(alpha = 0.95f)),
        border = BorderStroke(1.dp, if (allOk) SuccessMain.copy(alpha = 0.3f) else ErrorMain.copy(alpha = 0.3f))
    ) {
        Column(Modifier.padding(horizontal = 14.dp, vertical = 10.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth().clickable { expanded = !expanded },
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(
                        if (allOk) Icons.Outlined.MarkChatRead else Icons.Outlined.SmsFailed,
                        contentDescription = null,
                        tint = if (allOk) SuccessMain else ErrorMain,
                        modifier = Modifier.size(20.dp)
                    )
                    Spacer(modifier = Modifier.width(10.dp))
                    Column {
                        Text(
                            if (allOk) "Guardian SMS Ready" else "Alert Permissions Needed",
                            fontWeight = FontWeight.Bold,
                            color = PrimaryDark,
                            fontSize = 13.sp
                        )
                        if (allOk && !expanded) {
                            Text("Click to view settings", fontSize = 10.sp, color = TextSubtitle)
                        }
                    }
                }
                Icon(
                    if (expanded) Icons.Default.ExpandLess else Icons.Default.ExpandMore,
                    contentDescription = null,
                    tint = TextSubtitle,
                    modifier = Modifier.size(20.dp)
                )
            }

            AnimatedVisibility(visible = expanded) {
                Column {
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        "One‑week absence alerts reach parents via SMS. Ensure a physical SIM is active and permissions are allowed.",
                        fontSize = 12.sp,
                        color = Color.Gray,
                        lineHeight = 16.sp
                    )
                    Spacer(modifier = Modifier.height(10.dp))
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        Button(
                            onClick = onRequestPermissions,
                            colors = ButtonDefaults.buttonColors(containerColor = PrimaryDark),
                            shape = RoundedCornerShape(12.dp)
                        ) {
                            Text("Request Permissions", fontSize = 12.sp)
                        }
                        OutlinedButton(
                            onClick = onOpenAppSettings,
                            shape = RoundedCornerShape(12.dp)
                        ) {
                            Text("App Settings", fontSize = 12.sp)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun MeshBackground() {
    val infiniteTransition = rememberInfiniteTransition(label = "Mesh")
    val xOffset1 by infiniteTransition.animateFloat(
        initialValue = 0f, targetValue = 100f,
        animationSpec = infiniteRepeatable(tween(10000, easing = LinearEasing), RepeatMode.Reverse), label = "x1"
    )
    val yOffset1 by infiniteTransition.animateFloat(
        initialValue = 0f, targetValue = 150f,
        animationSpec = infiniteRepeatable(tween(8000, easing = LinearEasing), RepeatMode.Reverse), label = "y1"
    )

    Box(modifier = Modifier.fillMaxSize()) {
        Canvas(modifier = Modifier.fillMaxSize()) {
            drawRect(brush = Brush.verticalGradient(listOf(BgStart, BgEnd)))
            drawCircle(
                brush = Brush.radialGradient(
                    colors = listOf(PrimaryMain.copy(alpha = 0.08f), Color.Transparent),
                    center = Offset(200f + xOffset1, 300f + yOffset1),
                    radius = 800f
                ),
                center = Offset(200f + xOffset1, 300f + yOffset1), radius = 800f
            )
            drawCircle(
                brush = Brush.radialGradient(
                    colors = listOf(SecondaryMain.copy(alpha = 0.08f), Color.Transparent),
                    center = Offset(size.width - 200f - xOffset1, size.height - 300f - yOffset1),
                    radius = 1000f
                ),
                center = Offset(size.width - 200f - xOffset1, size.height - 300f - yOffset1), radius = 1000f
            )
        }
    }
}

@Composable
private fun AttendanceApp() {
    val context = LocalContext.current
    val activity = remember(context) { context as? ComponentActivity }
    var currentRole by remember { mutableStateOf<Role?>(null) }
    var teacherTab by remember { mutableStateOf(TeacherTab.MONITOR) }
    var rfidInput by remember { mutableStateOf("") }
    var statusMessage by remember { mutableStateOf("Ready to secure BNHS.") }
    var showTopControls by remember { mutableStateOf(true) }
    var attendanceWorkingDate by remember { mutableStateOf(nowInPhilippines().toLocalDate()) }
    var showClearHistoryConfirm by remember { mutableStateOf(false) }
    var lastScannedStudent by remember { mutableStateOf<Student?>(null) }

    fun exitPortalToSplash() {
        currentRole = null
        teacherTab = TeacherTab.MONITOR
        rfidInput = ""
        attendanceWorkingDate = nowInPhilippines().toLocalDate()
        statusMessage = "Ready to secure BNHS."
    }

    val students = remember {
        mutableStateListOf(
            Student(1, "Santos, Ana", "1111110001", "RFID-ANA-001", "Santos, Maria", "09943621529"),
            Student(2, "Cruz, Bryan", "1111110002", "RFID-BRY-002", "Cruz, Ricardo", "09178234561"),
            Student(3, "Dela Cruz, Ivan", "1111110003", "RFID-IVA-003", "Dela Cruz, Nora", "09663549820")
        )
    }
    val validRfidSet = remember(students) { students.map { it.rfidUid.uppercase() }.toSet() }
    val records = remember { mutableStateListOf<AttendanceRecord>() }

    LaunchedEffect(attendanceWorkingDate, students.size) {
        ensureDailyAbsenceDefaults(
            students = students,
            records = records,
            date = attendanceWorkingDate
        )
    }

    val permissionLauncher = rememberLauncherForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) { result ->
        val allOk = result.values.all { it }
        statusMessage = if (allOk) "Permissions granted." else "Some permissions denied."
    }

    Box(modifier = Modifier.fillMaxSize()) {
        MeshBackground()

        Scaffold(
            containerColor = Color.Transparent,
            topBar = {
                ElegantHeader(
                    role = currentRole,
                    onSelectRole = { if (it == null) exitPortalToSplash() else currentRole = it },
                    attendanceDate = attendanceWorkingDate,
                    showTopControls = showTopControls,
                    onToggleTopControls = { showTopControls = !showTopControls },
                    onBack = if (currentRole != null) { { exitPortalToSplash() } } else null
                )
            },
            bottomBar = {
                if (currentRole == Role.TEACHER) {
                    FloatingNavBar(selectedTab = teacherTab, onTabSelect = { teacherTab = it })
                }
            }
        ) { padding ->
            BackHandler(enabled = currentRole != null) { exitPortalToSplash() }
            Column(modifier = Modifier.fillMaxSize().padding(padding).padding(horizontal = 20.dp)) {
                Spacer(modifier = Modifier.height(8.dp))

                AnimatedVisibility(visible = showTopControls) {
                    Column {
                        ParentAlertPermissionCard(
                            context = context,
                            onRequestPermissions = { permissionLauncher.launch(permissionsNeededForParentAlerts(context)) },
                            onOpenAppSettings = {
                                context.startActivity(Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS).apply {
                                    data = Uri.fromParts("package", context.packageName, null)
                                })
                            }
                        )
                        Spacer(modifier = Modifier.height(8.dp))
                        if (currentRole != null) {
                            AttendanceRecordingDateBar(
                                attendanceWorkingDate = attendanceWorkingDate,
                                onPickDate = {
                                    activity?.promptAttendanceRecordingDatePicker(
                                        attendanceWorkingDate,
                                        nowInPhilippines().toLocalDate()
                                    ) { picked ->
                                        attendanceWorkingDate = picked
                                    }
                                },
                                onJumpToday = { attendanceWorkingDate = nowInPhilippines().toLocalDate() }
                            )
                            Spacer(modifier = Modifier.height(8.dp))
                        }
                    }
                }

                AnimatedContent(
                    modifier = Modifier.weight(1f).fillMaxWidth(),
                    targetState = currentRole,
                    label = "RoleTransition"
                ) { role ->
                    Box(Modifier.fillMaxSize()) {
                        when (role) {
                            null -> SplashPortal(onSelect = { currentRole = it })
                            Role.SECURITY_GUARD -> GateTerminal(
                                rfidInput = rfidInput,
                                onRfidChange = { rfidInput = it },
                                knownRfids = validRfidSet,
                                lastScannedStudent = lastScannedStudent,
                                attendanceDate = attendanceWorkingDate,
                                onClearHistory = { showClearHistoryConfirm = true },
                                onScan = { uid ->
                                    val s = students.firstOrNull { it.rfidUid.equals(uid.trim(), true) }
                                    if (s != null) {
                                        upsertAttendanceRecord(
                                            records = records,
                                            studentId = s.id,
                                            date = attendanceWorkingDate,
                                            loggedAt = philippinesDateTimeForAttendanceDay(attendanceWorkingDate),
                                            status = "PRESENT",
                                            loggedBy = "GATE"
                                        )
                                        statusMessage = "Access: ${s.name}"
                                        rfidInput = ""
                                        lastScannedStudent = s
                                        gateAccessFeedback(context, true)
                                        postAttendanceLoggedNotification(context, s.name, "Present at gate")
                                        true
                                    } else {
                                        gateAccessFeedback(context, false)
                                        statusMessage = "Invalid RFID"
                                        lastScannedStudent = null
                                        false
                                    }
                                }
                            )
                            Role.TEACHER -> AcademicDashboard(
                                tab = teacherTab,
                                students = students,
                                records = records,
                                attendanceDate = attendanceWorkingDate,
                                onClearHistory = { showClearHistoryConfirm = true },
                                onMark = { s, st ->
                                    upsertAttendanceRecord(
                                        records = records,
                                        studentId = s.id,
                                        date = attendanceWorkingDate,
                                        loggedAt = philippinesDateTimeForAttendanceDay(attendanceWorkingDate),
                                        status = st,
                                        loggedBy = "TEACHER"
                                    )
                                    if (st == "ABSENT") {
                                        if (consecutiveAbsentCalendarDaysEndingOn(records, s.id, attendanceWorkingDate) == PARENT_ALERT_CONSECUTIVE_ABSENT_DAYS) {
                                            postParentAbsenceAlerts(context, s.name, s.parentName, s.parentContact) { statusMessage = it }
                                        }
                                    }
                                },
                                onUpdateParentDetails = { studentId, newParentName, newParentContact ->
                                    val idx = students.indexOfFirst { it.id == studentId }
                                    if (idx >= 0) {
                                        students[idx] = students[idx].copy(
                                            parentName = newParentName.trim(),
                                            parentContact = newParentContact.trim()
                                        )
                                        statusMessage = "Parent details updated for ${students[idx].name}"
                                    }
                                },
                            )
                        }
                    }
                }
                QuickStatus(message = statusMessage)
                Spacer(modifier = Modifier.height(16.dp))
            }
        }
    }

    if (showClearHistoryConfirm) {
        AlertDialog(
            onDismissRequest = { showClearHistoryConfirm = false },
            icon = { Icon(Icons.Default.DeleteSweep, null, tint = ErrorMain) },
            title = { Text("Clear attendance history?") },
            text = {
                Text(
                    "This will delete all attendance records from Security and Teacher views for all dates. Parent details will stay.",
                    color = TextSubtitle
                )
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        records.clear()
                        lastScannedStudent = null
                        statusMessage = "Attendance history cleared."
                        showClearHistoryConfirm = false
                    }
                ) { Text("Clear", color = ErrorMain, fontWeight = FontWeight.Bold) }
            },
            dismissButton = {
                TextButton(onClick = { showClearHistoryConfirm = false }) { Text("Cancel") }
            }
        )
    }
}

@Composable
private fun ElegantHeader(
    role: Role?,
    onSelectRole: (Role?) -> Unit,
    attendanceDate: LocalDate,
    showTopControls: Boolean,
    onToggleTopControls: () -> Unit,
    onBack: (() -> Unit)? = null,
) {
    var menuExpanded by remember { mutableStateOf(false) }

    Surface(
        modifier = Modifier.fillMaxWidth().shadow(12.dp, RoundedCornerShape(bottomStart = 32.dp, bottomEnd = 32.dp)),
        color = PrimaryDark,
        shape = RoundedCornerShape(bottomStart = 32.dp, bottomEnd = 32.dp)
    ) {
        Column(modifier = Modifier.padding(start = 24.dp, end = 16.dp, top = 20.dp, bottom = 24.dp).statusBarsPadding()) {
            Row(modifier = Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                if (onBack != null) {
                    IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, null, tint = Color.White) }
                }
                Column(modifier = Modifier.weight(1f).clickable { if (role != null) menuExpanded = true }) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text("BNHSTrack Pro", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Black, color = Color.White)
                        if (role != null) Icon(Icons.Default.ArrowDropDown, null, tint = SecondaryMain)
                    }
                    Text("Bawing National High School", style = MaterialTheme.typography.labelMedium, color = SecondaryMain)

                    DropdownMenu(
                        expanded = menuExpanded,
                        onDismissRequest = { menuExpanded = false },
                        modifier = Modifier.background(Color.White).border(1.dp, BgEnd, RoundedCornerShape(12.dp))
                    ) {
                        DropdownMenuItem(
                            text = { Text("Gate Security", fontWeight = FontWeight.Bold) },
                            onClick = { onSelectRole(Role.SECURITY_GUARD); menuExpanded = false },
                            leadingIcon = { Icon(Icons.Default.Security, null, tint = PrimaryMain) }
                        )
                        DropdownMenuItem(
                            text = { Text("Academic Portal", fontWeight = FontWeight.Bold) },
                            onClick = { onSelectRole(Role.TEACHER); menuExpanded = false },
                            leadingIcon = { Icon(Icons.Default.School, null, tint = SecondaryMain) }
                        )
                        HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))
                        DropdownMenuItem(
                            text = { Text("Sign Out", color = ErrorMain, fontWeight = FontWeight.Bold) },
                            onClick = { onSelectRole(null); menuExpanded = false },
                            leadingIcon = { Icon(Icons.Default.Logout, null, tint = ErrorMain) }
                        )
                    }
                }
                Surface(
                    modifier = Modifier.size(36.dp),
                    shape = CircleShape,
                    color = Color.White.copy(alpha = 0.12f)
                ) {
                    IconButton(
                        onClick = onToggleTopControls,
                        modifier = Modifier.fillMaxSize()
                    ) {
                        Icon(
                            if (showTopControls) Icons.Default.ExpandLess else Icons.Default.ExpandMore,
                            null,
                            tint = Color.White
                        )
                    }
                }
            }
            if (role != null) {
                Text(attendanceDate.format(DateTimeFormatter.ofPattern("EEE, MMM d, yyyy")), color = Color.White.copy(0.9f), fontSize = 13.sp)
            }
        }
    }
}

@Composable
private fun AttendanceRecordingDateBar(
    attendanceWorkingDate: LocalDate,
    onPickDate: () -> Unit,
    onJumpToday: () -> Unit
) {
    Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Row(Modifier.padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
            Icon(Icons.Default.CalendarToday, null, tint = PrimaryMain, modifier = Modifier.size(20.dp))
            Spacer(modifier = Modifier.width(12.dp))
            Column(Modifier.weight(1f)) {
                Text("Roster Date", fontSize = 10.sp, fontWeight = FontWeight.Bold, color = TextSubtitle)
                Text(attendanceWorkingDate.format(DateTimeFormatter.ofPattern("MMM dd, yyyy")), fontWeight = FontWeight.Black, fontSize = 15.sp)
            }
            Row {
                IconButton(onClick = onPickDate) { Icon(Icons.Default.EditCalendar, null, tint = PrimaryMain) }
                IconButton(onClick = onJumpToday) { Icon(Icons.Default.Today, null, tint = SecondaryMain) }
            }
        }
    }
}

@Composable
private fun SplashPortal(onSelect: (Role) -> Unit) {
    Column(modifier = Modifier.fillMaxSize(), verticalArrangement = Arrangement.Center, horizontalAlignment = Alignment.CenterHorizontally) {
        Surface(modifier = Modifier.size(100.dp), shape = CircleShape, color = PrimaryMain, shadowElevation = 8.dp) {
            Icon(Icons.Default.Fingerprint, null, tint = Color.White, modifier = Modifier.padding(24.dp))
        }
        Spacer(modifier = Modifier.height(24.dp))
        Text("Executive Access", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Black)
        Text("Bawing National High School Attendance", color = Color.Gray, fontSize = 13.sp, modifier = Modifier.padding(bottom = 32.dp))

        SplashCard("Security Gate", "RFID Entrance Terminal", Icons.Default.Security, PrimaryMain) { onSelect(Role.SECURITY_GUARD) }
        Spacer(modifier = Modifier.height(16.dp))
        SplashCard("Academic Portal", "Monitoring & Alerts", Icons.Default.AutoStories, SecondaryMain) { onSelect(Role.TEACHER) }
    }
}

@Composable
private fun SplashCard(title: String, sub: String, icon: ImageVector, color: Color, onClick: () -> Unit) {
    Card(onClick = onClick, modifier = Modifier.fillMaxWidth().shadow(8.dp, RoundedCornerShape(24.dp)), shape = RoundedCornerShape(24.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Row(Modifier.padding(20.dp), verticalAlignment = Alignment.CenterVertically) {
            Surface(modifier = Modifier.size(48.dp), shape = RoundedCornerShape(12.dp), color = color.copy(0.1f)) {
                Icon(icon, null, tint = color, modifier = Modifier.padding(12.dp))
            }
            Spacer(modifier = Modifier.width(16.dp))
            Column {
                Text(title, fontWeight = FontWeight.Bold, fontSize = 17.sp)
                Text(sub, fontSize = 12.sp, color = Color.Gray)
            }
            Spacer(modifier = Modifier.weight(1f))
            Icon(Icons.Default.ChevronRight, null, tint = color.copy(0.5f))
        }
    }
}

@Composable
private fun GateTerminal(
    rfidInput: String,
    onRfidChange: (String) -> Unit,
    knownRfids: Set<String>,
    lastScannedStudent: Student?,
    attendanceDate: LocalDate,
    onClearHistory: () -> Unit,
    onScan: (String) -> Unit
) {
    val focusRequester = remember { FocusRequester() }
    val scrollState = rememberScrollState()
    LaunchedEffect(Unit) { focusRequester.requestFocus() }

    Column(
        Modifier
            .fillMaxSize()
            .verticalScroll(scrollState)
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text("Security Scanner", fontWeight = FontWeight.Bold, color = PrimaryDark, fontSize = 16.sp)
            TextButton(onClick = onClearHistory) {
                Icon(Icons.Default.DeleteSweep, null, tint = ErrorMain, modifier = Modifier.size(16.dp))
                Spacer(modifier = Modifier.width(6.dp))
                Text("Clear history", color = ErrorMain)
            }
        }
        Spacer(modifier = Modifier.height(8.dp))

        Card(
            modifier = Modifier.fillMaxWidth().shadow(12.dp, RoundedCornerShape(22.dp)),
            shape = RoundedCornerShape(22.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White)
        ) {
            Column(
                Modifier.padding(horizontal = 16.dp, vertical = 14.dp),
                horizontalAlignment = Alignment.Start
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Surface(modifier = Modifier.size(44.dp), shape = CircleShape, color = PrimaryMain.copy(alpha = 0.12f)) {
                        Icon(Icons.Default.Nfc, null, tint = PrimaryMain, modifier = Modifier.padding(10.dp))
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column(Modifier.weight(1f)) {
                        Text("RFID Scanner", fontWeight = FontWeight.Black, color = PrimaryDark, fontSize = 16.sp)
                        Text("Tap card or type UID below", color = TextSubtitle, fontSize = 12.sp)
                    }
                }
                Spacer(modifier = Modifier.height(12.dp))
                OutlinedTextField(
                    value = rfidInput,
                    onValueChange = {
                        onRfidChange(it)
                        if (it.length > 8 && knownRfids.contains(it.uppercase())) onScan(it)
                    },
                    modifier = Modifier
                        .fillMaxWidth()
                        .focusRequester(focusRequester),
                    singleLine = true,
                    label = { Text("RFID UID") },
                    placeholder = { Text("e.g. RFID-ANA-001") },
                    trailingIcon = {
                        IconButton(onClick = { onScan(rfidInput) }) {
                            Icon(Icons.Default.Search, contentDescription = "Scan entered UID", tint = PrimaryMain)
                        }
                    }
                )
                Spacer(modifier = Modifier.height(10.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Button(
                        onClick = { onScan(rfidInput) },
                        modifier = Modifier.weight(1f).height(44.dp),
                        shape = RoundedCornerShape(12.dp)
                    ) {
                        Text("Check UID", fontSize = 13.sp)
                    }
                    OutlinedButton(
                        onClick = { onRfidChange("") },
                        modifier = Modifier.weight(1f).height(44.dp),
                        shape = RoundedCornerShape(12.dp)
                    ) {
                        Text("Clear", fontSize = 13.sp)
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(10.dp))

        Surface(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(14.dp),
            color = Color.White.copy(alpha = 0.95f)
        ) {
            Row(Modifier.padding(horizontal = 12.dp, vertical = 10.dp), verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Outlined.Info, null, tint = SecondaryMain, modifier = Modifier.size(16.dp))
                Spacer(modifier = Modifier.width(8.dp))
                Text(
                    "Tip: Valid scan marks PRESENT automatically for selected roster date.",
                    fontSize = 11.sp,
                    color = TextSubtitle
                )
            }
        }
        if (lastScannedStudent != null) {
            Spacer(modifier = Modifier.height(8.dp))
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(14.dp),
                colors = CardDefaults.cardColors(containerColor = Color.White)
            ) {
                Column(Modifier.padding(horizontal = 12.dp, vertical = 10.dp)) {
                    Text("Student Details", fontWeight = FontWeight.Bold, color = PrimaryDark, fontSize = 12.sp)
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(lastScannedStudent.name, fontWeight = FontWeight.Bold, color = PrimaryDark)
                    Text("LRN: ${lastScannedStudent.lrn}", fontSize = 11.sp, color = TextSubtitle)
                    Text("RFID: ${lastScannedStudent.rfidUid}", fontSize = 11.sp, color = TextSubtitle)
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(
                        "Status: PRESENT • ${attendanceDate.format(DateTimeFormatter.ofPattern("MMM d, yyyy"))}",
                        color = SuccessMain,
                        fontWeight = FontWeight.Bold,
                        fontSize = 12.sp
                    )
                }
            }
        }
    }
}

@Composable
private fun AcademicDashboard(
    tab: TeacherTab,
    students: List<Student>,
    records: List<AttendanceRecord>,
    attendanceDate: LocalDate,
    onClearHistory: () -> Unit,
    onMark: (Student, String) -> Unit,
    onUpdateParentDetails: (studentId: Int, parentName: String, parentContact: String) -> Unit
) {
    Column(modifier = Modifier.fillMaxSize()) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text("Teacher Tools", color = PrimaryDark, fontWeight = FontWeight.Bold, fontSize = 14.sp)
            TextButton(onClick = onClearHistory) {
                Icon(Icons.Default.DeleteSweep, null, tint = ErrorMain, modifier = Modifier.size(16.dp))
                Spacer(modifier = Modifier.width(6.dp))
                Text("Clear history", color = ErrorMain)
            }
        }
        Spacer(modifier = Modifier.height(4.dp))

        when (tab) {
            TeacherTab.MONITOR -> {
                Text(
                    "Mark status for ${attendanceDate.format(DateTimeFormatter.ofPattern("MMM d, yyyy"))}.",
                    fontSize = 12.sp,
                    color = TextSubtitle,
                    modifier = Modifier.padding(bottom = 10.dp)
                )
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    items(students, key = { it.id }) { s ->
                        val rec = decisiveRecordForDay(records, s.id, attendanceDate)
                        StudentCard(s, rec?.status ?: "ABSENT", onMark)
                    }
                }
            }

            TeacherTab.HISTORY -> {
                val sorted = records.sortedByDescending { it.loggedAt }
                if (sorted.isEmpty()) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text("No attendance history yet.", color = Color.Gray)
                    }
                } else {
                    Column(modifier = Modifier.fillMaxSize()) {
                        AttendanceTableHeader()
                        Spacer(modifier = Modifier.height(6.dp))
                        LazyColumn(
                            modifier = Modifier.fillMaxSize(),
                            verticalArrangement = Arrangement.spacedBy(6.dp)
                        ) {
                            items(sorted, key = { "${it.studentId}_${it.loggedAt}" }) { rec ->
                                val studentName = students.firstOrNull { it.id == rec.studentId }?.name ?: "Student #${rec.studentId}"
                                AttendanceTableRow(rec, studentName)
                            }
                        }
                    }
                }
            }

            TeacherTab.ALERTS -> {
                val atRisk = students.mapNotNull { s ->
                    val streak = consecutiveAbsentCalendarDaysEndingOn(records, s.id, attendanceDate)
                    if (streak >= PARENT_ALERT_CONSECUTIVE_ABSENT_DAYS) s to streak else null
                }
                if (atRisk.isEmpty()) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text("No one-week absence alerts.", color = Color.Gray)
                    }
                } else {
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        verticalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        items(atRisk, key = { it.first.id }) { (student, streak) ->
                            AbsenceAlertRow(student, streak)
                        }
                    }
                }
            }

            TeacherTab.PARENTS -> {
                ParentDetailsEditor(
                    students = students,
                    onSave = onUpdateParentDetails
                )
            }
        }
    }
}

@Composable
private fun ParentDetailsEditor(
    students: List<Student>,
    onSave: (studentId: Int, parentName: String, parentContact: String) -> Unit
) {
    var selectedStudentId by remember(students) { mutableStateOf(students.firstOrNull()?.id) }
    val selected = students.firstOrNull { it.id == selectedStudentId } ?: return
    var parentNameInput by remember(selected.id, selected.parentName) { mutableStateOf(selected.parentName) }
    var parentContactInput by remember(selected.id, selected.parentContact) { mutableStateOf(selected.parentContact) }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
    ) {
        Text(
            "Adviser Parent Details Editor",
            fontWeight = FontWeight.Bold,
            color = PrimaryDark,
            fontSize = 15.sp
        )
        Spacer(modifier = Modifier.height(10.dp))

        LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            items(students, key = { it.id }) { s ->
                val active = s.id == selectedStudentId
                FilterChip(
                    selected = active,
                    onClick = { selectedStudentId = s.id },
                    label = { Text(s.name.substringBefore(",").trim(), fontSize = 12.sp) }
                )
            }
        }

        Spacer(modifier = Modifier.height(14.dp))

        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White)
        ) {
            Column(Modifier.padding(14.dp)) {
                Text("Student: ${selected.name}", color = PrimaryDark, fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                Spacer(modifier = Modifier.height(10.dp))
                OutlinedTextField(
                    value = parentNameInput,
                    onValueChange = { parentNameInput = it },
                    label = { Text("Parent / Guardian Name") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
                Spacer(modifier = Modifier.height(10.dp))
                OutlinedTextField(
                    value = parentContactInput,
                    onValueChange = { parentContactInput = it },
                    label = { Text("Parent Phone Number") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
                Spacer(modifier = Modifier.height(12.dp))
                Button(
                    onClick = {
                        onSave(selected.id, parentNameInput, parentContactInput)
                    },
                    shape = RoundedCornerShape(12.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = PrimaryDark)
                ) {
                    Icon(Icons.Default.Save, null, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Save Parent Details")
                }
            }
        }
    }
}

@Composable
private fun AttendanceTableHeader() {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(10.dp),
        color = PrimaryDark.copy(alpha = 0.08f)
    ) {
        Row(Modifier.padding(horizontal = 10.dp, vertical = 8.dp), verticalAlignment = Alignment.CenterVertically) {
            Text("Name", Modifier.weight(2.2f), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = PrimaryDark)
            Text("Date", Modifier.weight(1.2f), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = PrimaryDark)
            Text("Time", Modifier.weight(1f), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = PrimaryDark)
            Text("Status", Modifier.weight(1f), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = PrimaryDark)
            Text("By", Modifier.weight(1f), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = PrimaryDark)
        }
    }
}

@Composable
private fun AttendanceTableRow(record: AttendanceRecord, studentName: String) {
    val statusColor = if (record.status == "PRESENT") SuccessMain else ErrorMain
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(10.dp),
        color = Color.White
    ) {
        Row(Modifier.padding(horizontal = 10.dp, vertical = 9.dp), verticalAlignment = Alignment.CenterVertically) {
            Text(studentName, Modifier.weight(2.2f), fontSize = 12.sp, color = PrimaryDark, maxLines = 1)
            Text(record.date.format(DateTimeFormatter.ofPattern("MMM d")), Modifier.weight(1.2f), fontSize = 11.sp, color = TextSubtitle)
            Text(record.loggedAt.format(DateTimeFormatter.ofPattern("h:mm a")), Modifier.weight(1f), fontSize = 11.sp, color = TextSubtitle, maxLines = 1)
            Text(record.status, Modifier.weight(1f), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = statusColor, maxLines = 1)
            Text(record.loggedBy, Modifier.weight(1f), fontSize = 11.sp, color = TextSubtitle, maxLines = 1)
        }
    }
}

@Composable
private fun AbsenceAlertRow(student: Student, streak: Int) {
    var expanded by remember { mutableStateOf(false) }
    val parentMessage = "Good day, ${student.parentName}. This is the adviser of ${student.name}. We respectfully inform you that your child has been absent for one week. Please visit the school for a parent-teacher conference."

    Card(
        modifier = Modifier.fillMaxWidth().clickable { expanded = !expanded },
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White)
    ) {
        Column(Modifier.padding(horizontal = 14.dp, vertical = 12.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Default.WarningAmber, null, tint = AccentMain, modifier = Modifier.size(20.dp))
                Spacer(modifier = Modifier.width(8.dp))
                Text(student.name, fontWeight = FontWeight.Bold, color = PrimaryDark)
            }
            Spacer(modifier = Modifier.height(6.dp))
            Text("$streak consecutive absent days.", fontSize = 12.sp, color = TextSubtitle)
            Text("Parent: ${student.parentName}", fontSize = 12.sp, color = PrimaryDark.copy(alpha = 0.8f))
            Text(student.parentContact, fontSize = 13.sp, color = SecondaryMain, fontWeight = FontWeight.Bold)
            Spacer(modifier = Modifier.height(6.dp))
            Text(
                if (expanded) "Tap to hide notification message" else "Tap to view notification message",
                fontSize = 11.sp,
                color = PrimaryMain,
                fontWeight = FontWeight.SemiBold
            )
            AnimatedVisibility(visible = expanded) {
                Text(
                    parentMessage,
                    modifier = Modifier.padding(top = 6.dp),
                    fontSize = 11.sp,
                    lineHeight = 15.sp,
                    color = TextSubtitle
                )
            }
        }
    }
}

@Composable
private fun StudentCard(student: Student, status: String, onMark: (Student, String) -> Unit) {
    Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(20.dp), colors = CardDefaults.cardColors(containerColor = Color.White), elevation = CardDefaults.cardElevation(1.dp)) {
        Row(Modifier.padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
            Surface(modifier = Modifier.size(44.dp), shape = CircleShape, color = PrimaryMain.copy(0.1f)) {
                Box(contentAlignment = Alignment.Center) { Text(student.name.first().toString(), fontWeight = FontWeight.Bold, color = PrimaryMain) }
            }
            Spacer(modifier = Modifier.width(12.dp))
            Column(Modifier.weight(1f)) {
                Text(student.name, fontWeight = FontWeight.Bold)
                Text("LRN: ${student.lrn}", fontSize = 11.sp, color = Color.Gray)
            }
            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                Pill("P", SuccessMain, status == "PRESENT") { onMark(student, "PRESENT") }
                Pill("A", ErrorMain, status == "ABSENT") { onMark(student, "ABSENT") }
            }
        }
    }
}

@Composable
private fun Pill(label: String, color: Color, active: Boolean, onClick: () -> Unit) {
    Surface(
        modifier = Modifier.size(36.dp).clickable { onClick() },
        shape = CircleShape,
        color = if (active) color else color.copy(0.1f),
        border = if (active) null else BorderStroke(1.dp, color.copy(0.2f))
    ) {
        Box(contentAlignment = Alignment.Center) {
            Text(label, color = if (active) Color.White else color, fontWeight = FontWeight.Bold, fontSize = 12.sp)
        }
    }
}

@Composable
private fun FloatingNavBar(selectedTab: TeacherTab, onTabSelect: (TeacherTab) -> Unit) {
    Box(modifier = Modifier.fillMaxWidth().padding(16.dp), contentAlignment = Alignment.Center) {
        Surface(modifier = Modifier.shadow(16.dp, CircleShape), shape = CircleShape, color = PrimaryDark) {
            Row(modifier = Modifier.padding(horizontal = 8.dp, vertical = 6.dp)) {
                NavItem(Icons.Default.Dashboard, selectedTab == TeacherTab.MONITOR) { onTabSelect(TeacherTab.MONITOR) }
                NavItem(Icons.Default.History, selectedTab == TeacherTab.HISTORY) { onTabSelect(TeacherTab.HISTORY) }
                NavItem(Icons.Default.NotificationsActive, selectedTab == TeacherTab.ALERTS) { onTabSelect(TeacherTab.ALERTS) }
                NavItem(Icons.Default.Edit, selectedTab == TeacherTab.PARENTS) { onTabSelect(TeacherTab.PARENTS) }
            }
        }
    }
}

@Composable
private fun NavItem(icon: ImageVector, active: Boolean, onClick: () -> Unit) {
    IconButton(onClick = onClick, modifier = Modifier.background(if (active) Color.White.copy(0.1f) else Color.Transparent, CircleShape)) {
        Icon(icon, null, tint = if (active) SecondaryMain else Color.White.copy(0.5f))
    }
}

@Composable
private fun QuickStatus(message: String) {
    Surface(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(16.dp), color = PrimaryDark.copy(0.04f)) {
        Row(Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
            Icon(Icons.Default.Insights, null, tint = PrimaryMain, modifier = Modifier.size(16.dp))
            Spacer(modifier = Modifier.width(8.dp))
            Text(message, fontSize = 12.sp, color = PrimaryDark.copy(0.7f), fontWeight = FontWeight.Medium)
        }
    }
}

private enum class Role { SECURITY_GUARD, TEACHER }
private enum class TeacherTab { MONITOR, HISTORY, ALERTS, PARENTS }
