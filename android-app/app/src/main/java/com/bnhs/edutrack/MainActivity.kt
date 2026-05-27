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
import androidx.lifecycle.viewmodel.compose.viewModel
import com.bnhs.edutrack.auth.AuthRepository
import com.bnhs.edutrack.auth.AuthSession
import com.bnhs.edutrack.auth.AuthUiState
import com.bnhs.edutrack.auth.AuthViewModel
import com.bnhs.edutrack.auth.ForgotPasswordScreen
import com.bnhs.edutrack.auth.LoginScreen
import com.bnhs.edutrack.records.AdviserRecordsScreen
import com.bnhs.edutrack.records.AttendanceRecordsRepository
import com.bnhs.edutrack.records.GateRecordsScreen
import com.bnhs.edutrack.records.RecordsRepository
import com.bnhs.edutrack.records.rememberAdviserRecordsViewModel
import com.bnhs.edutrack.records.rememberGateRecordsViewModel
import com.bnhs.edutrack.profile.ProfileRepository
import com.bnhs.edutrack.profile.ProfileScreen
import com.bnhs.edutrack.profile.rememberProfileViewModel
import com.bnhs.edutrack.reports.AdviserMonthlyReportsScreen
import com.bnhs.edutrack.reports.MonthlyReportsRepository
import com.bnhs.edutrack.reports.rememberMonthlyReportsViewModel
import com.bnhs.edutrack.ui.*
import com.bnhs.edutrack.ui.LmsMeshBackground
import com.bnhs.edutrack.ui.lmsColorScheme
import com.bnhs.edutrack.rbac.RbacAccessDenied
import com.bnhs.edutrack.rbac.RbacEnforcer
import com.bnhs.edutrack.securityaudit.SecurityAlertNotifier
import com.bnhs.edutrack.rbac.RbacPermission
import com.bnhs.edutrack.records.rememberRecordsViewModel
import kotlinx.coroutines.launch
import androidx.compose.runtime.rememberCoroutineScope
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
    SecurityAlertNotifier.ensureChannel(context)
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
            MaterialTheme(colorScheme = lmsColorScheme(MobileAppRole.ADVISER)) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    AppRoot()
                }
            }
        }
    }
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
private fun AppRoot() {
    val context = LocalContext.current
    val authRepository = remember { AuthRepository.getInstance(context) }
    val authViewModel: AuthViewModel = viewModel(factory = AuthViewModel.Factory(authRepository))
    var showForgotPassword by remember { mutableStateOf(false) }

    when (val state = authViewModel.uiState) {
        AuthUiState.Checking -> {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = PrimaryMain)
            }
        }
        AuthUiState.Unauthenticated -> {
            if (showForgotPassword) {
                ForgotPasswordScreen(
                    viewModel = authViewModel,
                    onBack = { showForgotPassword = false },
                )
            } else {
                LoginScreen(
                    viewModel = authViewModel,
                    onForgotPassword = { showForgotPassword = true },
                )
            }
        }
        is AuthUiState.Authenticated -> {
            var liveSession by remember { mutableStateOf(state.session) }
            AttendanceApp(
                session = liveSession,
                onSessionUpdated = { liveSession = it },
                onAccountLogout = { authViewModel.logout() },
            )
        }
    }
}

@Composable
private fun AttendanceApp(
    session: AuthSession,
    onSessionUpdated: (AuthSession) -> Unit,
    onAccountLogout: () -> Unit,
) {
    val context = LocalContext.current
    val activity = remember(context) { context as? ComponentActivity }
    val authRepository = remember { AuthRepository.getInstance(context) }
    val rbac = remember(session) { RbacEnforcer.from(session) }
    val mobileRole = remember(session, rbac) { rbac.mobileAppRole() }
    var adminTab by remember { mutableStateOf(AdminTab.OVERVIEW) }
    var securityTab by remember { mutableStateOf(SecurityTab.SCAN) }
    var adviserTab by remember { mutableStateOf(AdviserTab.ROSTER) }
    var rfidInput by remember { mutableStateOf("") }
    var statusMessage by remember(mobileRole) {
        mutableStateOf(
            when (mobileRole) {
                MobileAppRole.ADMIN -> "Admin dashboard ready."
                MobileAppRole.SECURITY -> "Security gate ready."
                MobileAppRole.ADVISER -> "Adviser portal ready."
                MobileAppRole.UNSUPPORTED -> "This account has no mobile role."
            },
        )
    }
    var showTopControls by remember { mutableStateOf(true) }
    var attendanceWorkingDate by remember { mutableStateOf(nowInPhilippines().toLocalDate()) }
    var showClearHistoryConfirm by remember { mutableStateOf(false) }
    var lastScannedStudent by remember { mutableStateOf<Student?>(null) }
    val showAttendanceDateBar = mobileRole == MobileAppRole.SECURITY || mobileRole == MobileAppRole.ADVISER
    val showParentAlertCard = mobileRole == MobileAppRole.ADVISER && rbac.canManageAttendance()

    LaunchedEffect(session.user.id) {
        authRepository.touchSessionActivity()
    }

    LaunchedEffect(mobileRole, session.user.roles, session.user.permissions) {
        if (mobileRole == MobileAppRole.ADMIN) {
            val allowed = buildList {
                if (rbac.canViewDashboard()) add(AdminTab.OVERVIEW)
                if (rbac.canManageStudentRecords()) add(AdminTab.RECORDS)
                add(AdminTab.SYSTEM)
            }
            if (allowed.isNotEmpty() && adminTab !in allowed) {
                adminTab = allowed.first()
            }
        }
        if (mobileRole == MobileAppRole.SECURITY) {
            val allowed = buildList {
                if (rbac.canManageAttendance()) {
                    add(SecurityTab.SCAN)
                    add(SecurityTab.RECORDS)
                    add(SecurityTab.SCAN_LOG)
                }
            }
            if (allowed.isNotEmpty() && securityTab !in allowed) securityTab = allowed.first()
        }
        if (mobileRole == MobileAppRole.ADVISER) {
            val allowed = buildList {
                if (rbac.canManageAttendance()) {
                    add(AdviserTab.ROSTER)
                    add(AdviserTab.RECORDS)
                    add(AdviserTab.REPORTS)
                    add(AdviserTab.HISTORY)
                    add(AdviserTab.ALERTS)
                    add(AdviserTab.PARENTS)
                }
            }
            if (allowed.isNotEmpty() && adviserTab !in allowed) adviserTab = allowed.first()
        }
    }

    val students = remember { mutableStateListOf<Student>() }
    val scope = rememberCoroutineScope()
    val recordsRepository = remember { RecordsRepository.get(context) }
    val attendanceRepository = remember { AttendanceRecordsRepository.get(context) }
    val recordsViewModel = rememberRecordsViewModel(recordsRepository, session.user.email)
    val gateRecordsViewModel = rememberGateRecordsViewModel(attendanceRepository, session.user.email)
    val adviserRecordsViewModel = rememberAdviserRecordsViewModel(attendanceRepository, session.user.email)
    val monthlyReportsRepository = remember { MonthlyReportsRepository.get(context) }
    val monthlyReportsViewModel = rememberMonthlyReportsViewModel(monthlyReportsRepository)
    val profileRepository = remember { ProfileRepository.get(context) }
    val profileViewModel = rememberProfileViewModel(profileRepository, session, mobileRole)
    var showProfileScreen by remember { mutableStateOf(false) }

    LaunchedEffect(profileViewModel) {
        profileViewModel.onSessionUpdated = onSessionUpdated
    }

    val records = remember { mutableStateListOf<AttendanceRecord>() }

    fun reloadStudentsFromDb() {
        scope.launch {
            students.clear()
            students.addAll(recordsRepository.loadAppStudents())
        }
    }

    fun reloadAttendanceFromDb() {
        scope.launch {
            records.clear()
            records.addAll(attendanceRepository.loadAppAttendance())
        }
    }

    fun persistAttendance(record: AttendanceRecord) {
        scope.launch {
            attendanceRepository.upsertAppRecord(record, session.user.email)
            reloadAttendanceFromDb()
        }
    }

    LaunchedEffect(Unit) {
        recordsRepository.ensureSeedData()
        students.clear()
        students.addAll(recordsRepository.loadAppStudents())
        records.clear()
        records.addAll(attendanceRepository.loadAppAttendance())
        recordsViewModel.onRecordsChanged = { loaded ->
            students.clear()
            students.addAll(loaded)
            reloadAttendanceFromDb()
        }
        gateRecordsViewModel.onDataChanged = {
            reloadAttendanceFromDb()
            reloadStudentsFromDb()
        }
        gateRecordsViewModel.filterDate = attendanceWorkingDate
        adviserRecordsViewModel.onDataChanged = {
            reloadAttendanceFromDb()
            reloadStudentsFromDb()
        }
        adviserRecordsViewModel.filterDate = attendanceWorkingDate
    }

    LaunchedEffect(attendanceWorkingDate) {
        gateRecordsViewModel.filterDate = attendanceWorkingDate
        adviserRecordsViewModel.filterDate = attendanceWorkingDate
        if (mobileRole == MobileAppRole.SECURITY && securityTab == SecurityTab.RECORDS) {
            gateRecordsViewModel.refresh()
        }
        if (mobileRole == MobileAppRole.ADVISER && adviserTab == AdviserTab.RECORDS) {
            adviserRecordsViewModel.refresh()
        }
    }

    val validRfidSet = remember(students) { students.map { it.rfidUid.uppercase() }.toSet() }

    LaunchedEffect(attendanceWorkingDate, students.size, mobileRole) {
        if (mobileRole == MobileAppRole.ADVISER) {
            ensureDailyAbsenceDefaults(
                students = students,
                records = records,
                date = attendanceWorkingDate,
                now = philippinesDateTimeForAttendanceDay(attendanceWorkingDate),
            )
        }
    }

    val permissionLauncher = rememberLauncherForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) { result ->
        val allOk = result.values.all { it }
        statusMessage = if (allOk) "Permissions granted." else "Some permissions denied."
    }

    Box(modifier = Modifier.fillMaxSize()) {
        LmsMeshBackground()

        if (showProfileScreen) {
            ProfileScreen(
                viewModel = profileViewModel,
                repository = profileRepository,
                onBack = { showProfileScreen = false },
                onStatus = { statusMessage = it },
                modifier = Modifier.fillMaxSize(),
            )
        } else {
        Scaffold(
            containerColor = Color.Transparent,
            topBar = {
                RoleDashboardHeader(
                    mobileRole = mobileRole,
                    userLabel = session.user.name,
                    onOpenProfile = { showProfileScreen = true },
                    onAccountLogout = onAccountLogout,
                    attendanceDate = attendanceWorkingDate,
                    showDateLine = showAttendanceDateBar,
                    showTopControls = showTopControls,
                    onToggleTopControls = { showTopControls = !showTopControls },
                )
            },
            bottomBar = {
                when (mobileRole) {
                    MobileAppRole.ADMIN -> AdminBottomNav(adminTab, rbac) { adminTab = it }
                    MobileAppRole.SECURITY -> SecurityBottomNav(securityTab, rbac) { securityTab = it }
                    MobileAppRole.ADVISER -> AdviserBottomNav(adviserTab, rbac) { adviserTab = it }
                    MobileAppRole.UNSUPPORTED -> Unit
                }
            }
        ) { padding ->
            Column(modifier = Modifier.fillMaxSize().padding(padding).padding(horizontal = 20.dp)) {
                Spacer(modifier = Modifier.height(8.dp))

                AnimatedVisibility(visible = showTopControls) {
                    Column {
                        if (showParentAlertCard) {
                            ParentAlertPermissionCard(
                                context = context,
                                onRequestPermissions = { permissionLauncher.launch(permissionsNeededForParentAlerts(context)) },
                                onOpenAppSettings = {
                                    context.startActivity(Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS).apply {
                                        data = Uri.fromParts("package", context.packageName, null)
                                    })
                                },
                            )
                            Spacer(modifier = Modifier.height(8.dp))
                        }
                        if (showAttendanceDateBar) {
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

                Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
                    when (mobileRole) {
                        MobileAppRole.ADMIN -> AdminDashboard(
                            tab = adminTab,
                            session = session,
                            rbac = rbac,
                            recordsViewModel = recordsViewModel,
                            students = students,
                            records = records,
                            statusMessage = { statusMessage = it },
                            onDataRestored = {
                                scope.launch {
                                    reloadStudentsFromDb()
                                    reloadAttendanceFromDb()
                                    recordsViewModel.refresh()
                                }
                            },
                        )
                        MobileAppRole.SECURITY -> when (securityTab) {
                            SecurityTab.RECORDS -> GateRecordsScreen(
                                viewModel = gateRecordsViewModel,
                                rbac = rbac,
                                modifier = Modifier.fillMaxSize(),
                            )
                            SecurityTab.SCAN -> if (rbac.canManageAttendance()) {
                                GateTerminal(
                                rfidInput = rfidInput,
                                onRfidChange = { rfidInput = it },
                                knownRfids = validRfidSet,
                                lastScannedStudent = lastScannedStudent,
                                attendanceDate = attendanceWorkingDate,
                                onClearHistory = { showClearHistoryConfirm = true },
                                onScan = scan@{ uid ->
                                    if (!rbac.canManageAttendance()) {
                                        statusMessage = rbac.denyReason(RbacPermission.ATTENDANCE_MANAGE)
                                        return@scan
                                    }
                                    val s = students.firstOrNull { it.rfidUid.equals(uid.trim(), true) }
                                    if (s != null) {
                                        val rec = AttendanceRecord(
                                            studentId = s.id,
                                            date = attendanceWorkingDate,
                                            loggedAt = philippinesDateTimeForAttendanceDay(attendanceWorkingDate),
                                            status = "PRESENT",
                                            loggedBy = "GATE",
                                        )
                                        upsertAttendanceRecord(records, rec.studentId, rec.date, rec.loggedAt, rec.status, rec.loggedBy)
                                        persistAttendance(rec)
                                        statusMessage = "Access granted: ${s.name}"
                                        rfidInput = ""
                                        lastScannedStudent = s
                                        gateAccessFeedback(context, true)
                                        postAttendanceLoggedNotification(context, s.name, "Present at gate")
                                        true
                                    } else {
                                        gateAccessFeedback(context, false)
                                        statusMessage = "Invalid RFID card"
                                        lastScannedStudent = null
                                        false
                                    }
                                },
                            )
                            } else {
                                RbacAccessDenied(RbacPermission.ATTENDANCE_MANAGE, rbac, Modifier.fillMaxSize())
                            }
                            SecurityTab.SCAN_LOG -> if (rbac.canManageAttendance()) {
                                SecurityScanLogTab(
                                students = students,
                                records = records,
                                attendanceDate = attendanceWorkingDate,
                                onClearHistory = { showClearHistoryConfirm = true },
                            )
                            } else {
                                RbacAccessDenied(RbacPermission.ATTENDANCE_MANAGE, rbac, Modifier.fillMaxSize())
                            }
                        }
                        MobileAppRole.ADVISER -> when (adviserTab) {
                            AdviserTab.RECORDS -> AdviserRecordsScreen(
                                viewModel = adviserRecordsViewModel,
                                rbac = rbac,
                                modifier = Modifier.fillMaxSize(),
                            )
                            AdviserTab.REPORTS -> AdviserMonthlyReportsScreen(
                                viewModel = monthlyReportsViewModel,
                                rbac = rbac,
                                onStatus = { statusMessage = it },
                                modifier = Modifier.fillMaxSize(),
                            )
                            else -> if (rbac.canManageAttendance()) {
                                AdviserDashboard(
                            tab = adviserTab,
                            students = students,
                            records = records,
                            attendanceDate = attendanceWorkingDate,
                            onClearHistory = { showClearHistoryConfirm = true },
                            onMark = mark@{ s, st ->
                                if (!rbac.canManageAttendance()) {
                                    statusMessage = rbac.denyReason(RbacPermission.ATTENDANCE_MANAGE)
                                    return@mark
                                }
                                val rec = AttendanceRecord(
                                    studentId = s.id,
                                    date = attendanceWorkingDate,
                                    loggedAt = philippinesDateTimeForAttendanceDay(attendanceWorkingDate),
                                    status = st,
                                    loggedBy = "ADVISER",
                                )
                                upsertAttendanceRecord(records, rec.studentId, rec.date, rec.loggedAt, rec.status, rec.loggedBy)
                                persistAttendance(rec)
                                if (st == "ABSENT") {
                                    if (consecutiveAbsentCalendarDaysEndingOn(records, s.id, attendanceWorkingDate) == PARENT_ALERT_CONSECUTIVE_ABSENT_DAYS) {
                                        postParentAbsenceAlerts(context, s.name, s.parentName, s.parentContact) { statusMessage = it }
                                    }
                                }
                            },
                                onUpdateParentDetails = { studentId, newParentName, newParentContact ->
                                    scope.launch {
                                        com.bnhs.edutrack.data.BnhsRepository.get(context)
                                            .updateGuardianContact(studentId.toLong(), newParentName, newParentContact)
                                        reloadStudentsFromDb()
                                    }
                                    val idx = students.indexOfFirst { it.id == studentId }
                                    if (idx >= 0) {
                                        students[idx] = students[idx].copy(
                                            parentName = newParentName.trim(),
                                            parentContact = newParentContact.trim(),
                                        )
                                        statusMessage = "Parent details updated for ${students[idx].name}"
                                    }
                                },
                            )
                            } else {
                                RbacAccessDenied(RbacPermission.ATTENDANCE_MANAGE, rbac, Modifier.fillMaxSize())
                            }
                        }
                        MobileAppRole.UNSUPPORTED -> UnsupportedRoleScreen(
                            roles = session.user.roles,
                            permissions = session.user.permissions,
                            onLogout = onAccountLogout,
                        )
                    }
                }
                QuickStatus(message = statusMessage)
                Spacer(modifier = Modifier.height(16.dp))
            }
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
                    when (mobileRole) {
                        MobileAppRole.SECURITY -> "Clear gate scan history for this device?"
                        MobileAppRole.ADVISER -> "Clear adviser attendance marks for this device? Parent details stay."
                        else -> "Clear local attendance cache on this device?"
                    },
                    color = TextSubtitle,
                )
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        when (mobileRole) {
                            MobileAppRole.SECURITY -> {
                                scope.launch {
                                    com.bnhs.edutrack.data.BnhsDatabase.get(context).attendanceDao().deleteAllForRole("GATE")
                                    reloadAttendanceFromDb()
                                }
                                lastScannedStudent = null
                                statusMessage = "Gate scan history cleared."
                            }
                            MobileAppRole.ADVISER -> {
                                scope.launch {
                                    com.bnhs.edutrack.data.BnhsDatabase.get(context).attendanceDao().deleteAllForRole("ADVISER")
                                    com.bnhs.edutrack.data.BnhsDatabase.get(context).attendanceDao().deleteAllForRole("TEACHER")
                                    reloadAttendanceFromDb()
                                }
                                statusMessage = "Adviser attendance history cleared."
                            }
                            else -> {
                                records.clear()
                                statusMessage = "Local cache cleared."
                            }
                        }
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
private fun RoleDashboardHeader(
    mobileRole: MobileAppRole,
    userLabel: String,
    onOpenProfile: () -> Unit,
    onAccountLogout: () -> Unit,
    attendanceDate: LocalDate,
    showDateLine: Boolean,
    showTopControls: Boolean,
    onToggleTopControls: () -> Unit,
) {
    var menuExpanded by remember { mutableStateOf(false) }
    val headerGreen = com.bnhs.edutrack.ui.LmsColors.Navy
    val accentGreen = com.bnhs.edutrack.ui.LmsColors.GoldLight

    Surface(
        modifier = Modifier.fillMaxWidth().shadow(6.dp),
        color = headerGreen,
    ) {
        Column(modifier = Modifier.padding(start = 20.dp, end = 12.dp, top = 16.dp, bottom = 18.dp).statusBarsPadding()) {
            Row(modifier = Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Surface(shape = RoundedCornerShape(10.dp), color = com.bnhs.edutrack.ui.LmsColors.Gold, modifier = Modifier.size(40.dp)) {
                    Box(contentAlignment = Alignment.Center) {
                        Text("ET", fontWeight = FontWeight.Black, color = headerGreen, fontSize = 14.sp)
                    }
                }
                Spacer(modifier = Modifier.width(12.dp))
                Column(modifier = Modifier.weight(1f)) {
                    Text("EduTrack", fontWeight = FontWeight.Bold, color = Color.White, fontSize = 17.sp)
                    Text("SHS MANAGEMENT SYSTEM", color = Color.White.copy(alpha = 0.75f), fontSize = 9.sp, letterSpacing = 0.5.sp)
                    Text("${mobileRole.title} · $userLabel", color = accentGreen, fontSize = 11.sp, modifier = Modifier.padding(top = 2.dp))
                }
                if (mobileRole != MobileAppRole.UNSUPPORTED) {
                    IconButton(onClick = onToggleTopControls) {
                        Icon(
                            if (showTopControls) Icons.Default.ExpandLess else Icons.Default.ExpandMore,
                            null,
                            tint = Color.White,
                        )
                    }
                }
                IconButton(onClick = { menuExpanded = true }) {
                    Icon(Icons.Default.MoreVert, null, tint = Color.White)
                }
                DropdownMenu(expanded = menuExpanded, onDismissRequest = { menuExpanded = false }) {
                    DropdownMenuItem(
                        text = { Text("Profile", fontWeight = FontWeight.SemiBold) },
                        onClick = { menuExpanded = false; onOpenProfile() },
                        leadingIcon = { Icon(Icons.Default.Person, null, tint = headerGreen) },
                    )
                    DropdownMenuItem(
                        text = { Text("Sign out", color = ErrorMain, fontWeight = FontWeight.Bold) },
                        onClick = { menuExpanded = false; onAccountLogout() },
                        leadingIcon = { Icon(Icons.Default.Logout, null, tint = ErrorMain) },
                    )
                }
            }
            if (showDateLine) {
                Text(attendanceDate.format(DateTimeFormatter.ofPattern("EEE, MMM d, yyyy")), color = Color.White.copy(0.9f), fontSize = 13.sp)
            }
        }
    }
}

@Composable
private fun UnsupportedRoleScreen(roles: List<String>, permissions: List<String>, onLogout: () -> Unit) {
    Column(
        modifier = Modifier.fillMaxSize(),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Icon(Icons.Default.LockPerson, null, tint = ErrorMain, modifier = Modifier.size(56.dp))
        Spacer(modifier = Modifier.height(16.dp))
        Text("Mobile access not available", fontWeight = FontWeight.Bold, color = PrimaryDark, fontSize = 18.sp)
        Text(
            "Signed-in roles: ${roles.joinToString().ifBlank { "none" }}.\n" +
                "Permissions: ${permissions.joinToString().ifBlank { "none" }}.\n" +
                "Use admin@bnhs.local, security@bnhs.local, or adviser@bnhs.local.",
            textAlign = TextAlign.Center,
            color = TextSubtitle,
            fontSize = 13.sp,
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 12.dp),
        )
        Button(onClick = onLogout) { Text("Sign out") }
    }
}

@Composable
private fun SecurityScanLogTab(
    students: List<Student>,
    records: List<AttendanceRecord>,
    attendanceDate: LocalDate,
    onClearHistory: () -> Unit,
) {
    val gateRecords = records.filter { it.loggedBy == "GATE" && it.date == attendanceDate }
        .sortedByDescending { it.loggedAt }

    Column(Modifier.fillMaxSize()) {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            Text("Today's gate log", fontWeight = FontWeight.Bold, color = PrimaryDark)
            TextButton(onClick = onClearHistory) {
                Text("Clear", color = ErrorMain, fontSize = 12.sp)
            }
        }
        if (gateRecords.isEmpty()) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Text("No gate scans for this date.", color = TextSubtitle)
            }
        } else {
            LazyColumn(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                items(gateRecords, key = { "${it.studentId}_${it.loggedAt}" }) { rec ->
                    val name = students.firstOrNull { it.id == rec.studentId }?.name ?: "Unknown"
                    AttendanceTableRow(rec, name)
                }
            }
        }
    }
}

@Composable
fun SecurityBottomNav(selected: SecurityTab, rbac: RbacEnforcer, onSelect: (SecurityTab) -> Unit) {
    val items = buildList {
        if (rbac.canManageAttendance()) {
            add(Triple(SecurityTab.SCAN, Icons.Default.Nfc, "Scan"))
            add(Triple(SecurityTab.RECORDS, Icons.Default.FolderShared, "Records"))
            add(Triple(SecurityTab.SCAN_LOG, Icons.Default.ListAlt, "Log"))
        }
    }
    if (items.isNotEmpty()) {
        DashboardBottomNav(items = items, selected = selected, onSelect = onSelect)
    }
}

@Composable
fun AdviserBottomNav(selected: AdviserTab, rbac: RbacEnforcer, onSelect: (AdviserTab) -> Unit) {
    val items = buildList {
        if (rbac.canManageAttendance()) {
            add(Triple(AdviserTab.ROSTER, Icons.Default.FactCheck, "Roster"))
            add(Triple(AdviserTab.RECORDS, Icons.Default.FolderShared, "Records"))
            add(Triple(AdviserTab.REPORTS, Icons.Default.Assessment, "Monthly"))
            add(Triple(AdviserTab.HISTORY, Icons.Default.History, "History"))
            add(Triple(AdviserTab.ALERTS, Icons.Default.NotificationsActive, "Alerts"))
            add(Triple(AdviserTab.PARENTS, Icons.Default.FamilyRestroom, "Parents"))
        }
    }
    if (items.isNotEmpty()) {
        DashboardBottomNav(items = items, selected = selected, onSelect = onSelect)
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
private fun AdviserDashboard(
    tab: AdviserTab,
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
            Text("Adviser — Class Attendance", color = PrimaryDark, fontWeight = FontWeight.Bold, fontSize = 14.sp)
            TextButton(onClick = onClearHistory) {
                Icon(Icons.Default.DeleteSweep, null, tint = ErrorMain, modifier = Modifier.size(16.dp))
                Spacer(modifier = Modifier.width(6.dp))
                Text("Clear history", color = ErrorMain)
            }
        }
        Spacer(modifier = Modifier.height(4.dp))

        when (tab) {
            AdviserTab.RECORDS, AdviserTab.REPORTS -> Unit
            AdviserTab.ROSTER -> {
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

            AdviserTab.HISTORY -> {
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

            AdviserTab.ALERTS -> {
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

            AdviserTab.PARENTS -> {
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
    val statusColor = when (record.status.uppercase()) {
        "PRESENT" -> SuccessMain
        "LATE" -> LmsColors.Sage
        "EXCUSED" -> TextSubtitle
        else -> ErrorMain
    }
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
                val normalized = status.uppercase()
                Pill("P", SuccessMain, normalized == "PRESENT") { onMark(student, "PRESENT") }
                Pill("L", LmsColors.Sage, normalized == "LATE") { onMark(student, "LATE") }
                Pill("A", ErrorMain, normalized == "ABSENT") { onMark(student, "ABSENT") }
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
private fun QuickStatus(message: String) {
    Surface(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(16.dp), color = PrimaryDark.copy(0.04f)) {
        Row(Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
            Icon(Icons.Default.Insights, null, tint = PrimaryMain, modifier = Modifier.size(16.dp))
            Spacer(modifier = Modifier.width(8.dp))
            Text(message, fontSize = 12.sp, color = PrimaryDark.copy(0.7f), fontWeight = FontWeight.Medium)
        }
    }
}

