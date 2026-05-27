package com.bnhs.edutrack.reports

import com.bnhs.edutrack.ui.*

import android.content.Intent
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.bnhs.edutrack.network.MonthlyReportDetailDto
import com.bnhs.edutrack.network.MonthlyReportLineDto
import com.bnhs.edutrack.network.MonthlyReportSummaryDto
import com.bnhs.edutrack.rbac.RbacAccessDenied
import com.bnhs.edutrack.rbac.RbacEnforcer
import com.bnhs.edutrack.rbac.RbacPermission
import kotlinx.coroutines.launch
import java.time.Month
import java.time.YearMonth
import java.util.Locale

class MonthlyReportsViewModel(
    private val repository: MonthlyReportsRepository,
) : ViewModel() {
    var reports by mutableStateOf<List<MonthlyReportSummaryDto>>(emptyList())
    var selectedReportId by mutableStateOf<Long?>(null)
    var selectedReport by mutableStateOf<MonthlyReportDetailDto?>(null)
    var isLoading by mutableStateOf(false)
    var statusMessage by mutableStateOf("Load a monthly attendance report from the server.")

    fun refreshList(selectLatest: Boolean = true) {
        viewModelScope.launch {
            isLoading = true
            when (val result = repository.listReports()) {
                is ReportsResult.Success -> {
                    reports = result.value
                    if (reports.isEmpty()) {
                        selectedReportId = null
                        selectedReport = null
                        statusMessage = "No monthly report yet. Mark daily attendance in Roster, then tap Generate Attendance Records."
                    } else {
                        val pick = if (selectLatest) reports.first().id else selectedReportId
                        pick?.let { loadDetail(it) }
                            ?: run { statusMessage = "${reports.size} report(s) available. Select one below." }
                    }
                }
                is ReportsResult.Error -> {
                    statusMessage = result.message
                    selectedReport = null
                }
            }
            isLoading = false
        }
    }

    fun loadDetail(reportId: Long) {
        selectedReportId = reportId
        viewModelScope.launch {
            isLoading = true
            when (val result = repository.getReport(reportId)) {
                is ReportsResult.Success -> {
                    selectedReport = result.value
                    statusMessage = "${result.value.periodLabel.orEmpty()} — ${result.value.lines.orEmpty().size} students"
                }
                is ReportsResult.Error -> {
                    selectedReport = null
                    statusMessage = result.message
                }
            }
            isLoading = false
        }
    }

    fun selectReport(reportId: Long) {
        if (reportId != selectedReportId) loadDetail(reportId)
    }

    fun generateAttendanceRecords(onDone: (String) -> Unit) {
        val reportId = selectedReportId ?: return
        viewModelScope.launch {
            isLoading = true
            when (val result = repository.generateAttendanceRecords(reportId)) {
                is ReportsResult.Success -> {
                    val message = result.value.first
                    selectedReport = result.value.second
                    statusMessage = message
                    onDone(message)
                    // Pull latest list/order and force detail refresh to reflect complete lines.
                    refreshList(selectLatest = false)
                    loadDetail(reportId)
                }
                is ReportsResult.Error -> {
                    statusMessage = result.message
                    onDone(result.message)
                }
            }
            isLoading = false
        }
    }
}

@Composable
fun rememberMonthlyReportsViewModel(repository: MonthlyReportsRepository): MonthlyReportsViewModel {
    return remember(repository) { MonthlyReportsViewModel(repository) }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdviserMonthlyReportsScreen(
    viewModel: MonthlyReportsViewModel,
    rbac: RbacEnforcer,
    onStatus: (String) -> Unit,
    modifier: Modifier = Modifier,
) {
    if (!rbac.canManageAttendance()) {
        RbacAccessDenied(RbacPermission.ATTENDANCE_MANAGE, rbac, modifier)
        return
    }

    val context = LocalContext.current
    val report = viewModel.selectedReport
    val lines = report?.lines.orEmpty().sortedBy { it.studentName.orEmpty() }
    val horizontalScroll = rememberScrollState()

    LaunchedEffect(Unit) {
        viewModel.refreshList(selectLatest = true)
    }

    LaunchedEffect(viewModel.statusMessage) {
        onStatus(viewModel.statusMessage)
    }

    Column(modifier.fillMaxSize()) {
        Row(
            Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                Text("Monthly Attendance Report", fontWeight = FontWeight.Bold, color = PrimaryDark, fontSize = 16.sp)
                Text(
                    report?.let { "${it.section?.name.orEmpty()} · ${it.periodLabel.orEmpty()}" }
                        ?: "Select a report period",
                    fontSize = 11.sp,
                    color = TextSubtitle,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
            }
            IconButton(onClick = { viewModel.refreshList(selectLatest = false) }, enabled = !viewModel.isLoading) {
                Icon(Icons.Default.Refresh, "Refresh", tint = PrimaryMain)
            }
        }

        Text(
            "Totals match class attendance automatically. Tap Generate Attendance Records to save the report on the web (print/email).",
            fontSize = 11.sp,
            color = TextSubtitle,
            modifier = Modifier.padding(bottom = 8.dp),
        )

        if (viewModel.reports.isNotEmpty()) {
            val selectedSummary = viewModel.reports.firstOrNull { it.id == viewModel.selectedReportId }
            val years = viewModel.reports.mapNotNull { it.reportYear }.distinct().sortedDescending()
            val months = (1..12).toList()
            val monthName: (Int) -> String = { m -> Month.of(m).name.lowercase().replaceFirstChar { it.uppercase() } }

            var yearExpanded by remember { mutableStateOf(false) }
            var monthExpanded by remember { mutableStateOf(false) }
            var selectedYear by remember(viewModel.reports, viewModel.selectedReportId) {
                mutableStateOf(selectedSummary?.reportYear ?: years.firstOrNull())
            }
            var selectedMonth by remember(viewModel.reports, viewModel.selectedReportId) {
                mutableStateOf(selectedSummary?.reportMonth ?: months.first())
            }

            LaunchedEffect(selectedSummary?.id) {
                selectedSummary?.reportYear?.let { selectedYear = it }
                selectedSummary?.reportMonth?.let { selectedMonth = it }
            }

            LaunchedEffect(selectedYear, selectedMonth, viewModel.reports) {
                val match = viewModel.reports.firstOrNull {
                    it.reportYear == selectedYear && it.reportMonth == selectedMonth
                }
                if (match?.id != null && match.id != viewModel.selectedReportId) {
                    viewModel.selectReport(match.id)
                }
            }

            Row(
                Modifier.fillMaxWidth().padding(vertical = 6.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                ExposedDropdownMenuBox(
                    expanded = monthExpanded,
                    onExpandedChange = { monthExpanded = it },
                    modifier = Modifier.weight(1f),
                ) {
                    OutlinedTextField(
                        value = monthName(selectedMonth),
                        onValueChange = {},
                        readOnly = true,
                        modifier = Modifier.menuAnchor().fillMaxWidth(),
                        label = { Text("Month") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(monthExpanded) },
                        shape = RoundedCornerShape(12.dp),
                    )
                    ExposedDropdownMenu(expanded = monthExpanded, onDismissRequest = { monthExpanded = false }) {
                        months.forEach { month ->
                            DropdownMenuItem(
                                text = { Text(monthName(month), fontSize = 13.sp) },
                                onClick = {
                                    monthExpanded = false
                                    selectedMonth = month
                                },
                            )
                        }
                    }
                }

                ExposedDropdownMenuBox(
                    expanded = yearExpanded,
                    onExpandedChange = { yearExpanded = it },
                    modifier = Modifier.weight(1f),
                ) {
                    OutlinedTextField(
                        value = selectedYear?.toString() ?: "Year",
                        onValueChange = {},
                        readOnly = true,
                        modifier = Modifier.menuAnchor().fillMaxWidth(),
                        label = { Text("Year") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(yearExpanded) },
                        shape = RoundedCornerShape(12.dp),
                    )
                    ExposedDropdownMenu(expanded = yearExpanded, onDismissRequest = { yearExpanded = false }) {
                        years.forEach { year ->
                            DropdownMenuItem(
                                text = { Text(year.toString(), fontSize = 13.sp) },
                                onClick = {
                                    yearExpanded = false
                                    selectedYear = year
                                },
                            )
                        }
                    }
                }
            }
            Text(
                text = "Selected: ${monthName(selectedMonth)} ${selectedYear ?: ""}",
                fontSize = 11.sp,
                color = TextSubtitle,
                modifier = Modifier.padding(bottom = 6.dp),
            )
        }

        Row(
            Modifier.fillMaxWidth().padding(bottom = 8.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            Button(
                onClick = {
                    report?.let { r ->
                        context.startActivity(Intent.createChooser(MonthlyReportExcelExporter.shareExcel(context, r), "Send report"))
                    }
                },
                enabled = report != null && !viewModel.isLoading,
                modifier = Modifier.weight(1f),
                shape = RoundedCornerShape(12.dp),
                colors = ButtonDefaults.buttonColors(containerColor = PrimaryDark),
            ) {
                Icon(Icons.Default.Email, null, modifier = Modifier.size(18.dp))
                Spacer(Modifier.width(6.dp))
                Text("Share / Email", fontSize = 12.sp)
            }
            OutlinedButton(
                onClick = {
                    viewModel.generateAttendanceRecords(onStatus)
                },
                enabled = report != null && !viewModel.isLoading,
                modifier = Modifier.weight(1f),
                shape = RoundedCornerShape(12.dp),
            ) {
                Icon(Icons.Default.Download, null, modifier = Modifier.size(18.dp), tint = PrimaryMain)
                Spacer(Modifier.width(6.dp))
                Text("Generate Attendance Records", fontSize = 12.sp, color = PrimaryDark)
            }
        }

        if (viewModel.isLoading && report == null) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = PrimaryMain)
            }
        } else if (report == null) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Text(
                    viewModel.statusMessage,
                    textAlign = TextAlign.Center,
                    color = TextSubtitle,
                    fontSize = 13.sp,
                    modifier = Modifier.padding(24.dp),
                )
            }
        } else {
            val monthDays = run {
                val y = report.reportYear
                val m = report.reportMonth
                if (y != null && m != null && m in 1..12) YearMonth.of(y, m).lengthOfMonth() else null
            }
            Card(
                Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Row(
                    Modifier.padding(12.dp).fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceEvenly,
                ) {
                    StatChip("Month days", "${monthDays ?: 0}")
                    StatChip("School days", "${report.schoolDaysTotal ?: 0}")
                    StatChip("Students", "${lines.size}")
                    StatChip("Total absent", "${report.totalAbsentDays ?: 0}", ErrorMain)
                }
            }

            if (viewModel.isLoading) {
                LinearProgressIndicator(Modifier.fillMaxWidth().padding(top = 6.dp), color = PrimaryMain)
            }

            Row(
                Modifier
                    .fillMaxWidth()
                    .padding(top = 10.dp)
                    .horizontalScroll(horizontalScroll)
                    .background(PrimaryDark.copy(0.08f), RoundedCornerShape(topStart = 10.dp, topEnd = 10.dp))
                    .padding(horizontal = 8.dp, vertical = 8.dp),
            ) {
                MonthlyTableHeaderCell("#", 0.35f)
                MonthlyTableHeaderCell("Student", 1.6f)
                MonthlyTableHeaderCell("LRN", 0.9f)
                MonthlyTableHeaderCell("Days", 0.45f)
                MonthlyTableHeaderCell("Pres.", 0.45f)
                MonthlyTableHeaderCell("Late", 0.45f)
                MonthlyTableHeaderCell("Exc.", 0.45f)
                MonthlyTableHeaderCell("Absent", 0.55f)
                MonthlyTableHeaderCell("By day", 1.5f)
                MonthlyTableHeaderCell("Remarks", 1.0f)
            }

            LazyColumn(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(4.dp),
            ) {
                items(lines, key = { it.id ?: it.enrollmentId ?: 0L }) { line ->
                    MonthlyReportLineRow(line, horizontalScroll)
                }
            }
        }
    }
}

@Composable
private fun RowScope.MonthlyTableHeaderCell(text: String, weight: Float) {
    Text(
        text,
        Modifier.weight(weight),
        fontSize = 10.sp,
        fontWeight = FontWeight.Bold,
        color = PrimaryDark,
        maxLines = 1,
        overflow = TextOverflow.Ellipsis,
    )
}

@Composable
private fun MonthlyReportLineRow(line: MonthlyReportLineDto, scrollState: androidx.compose.foundation.ScrollState) {
    val absent = line.absentDays ?: 0
    Surface(
        shape = RoundedCornerShape(8.dp),
        color = Color.White,
        modifier = Modifier.fillMaxWidth(),
    ) {
        Row(
            Modifier
                .horizontalScroll(scrollState)
                .padding(horizontal = 8.dp, vertical = 10.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            MonthlyTableCell("${line.id}", 0.35f, TextSubtitle)
            MonthlyTableCell(line.studentName.orEmpty(), 1.6f, PrimaryDark, FontWeight.SemiBold)
            MonthlyTableCell(line.lrn.orEmpty().ifBlank { "—" }, 0.9f, TextSubtitle)
            MonthlyTableCell("${line.schoolDays ?: 0}", 0.45f, TextSubtitle, textAlign = TextAlign.Center)
            MonthlyTableCell("${line.presentDays ?: 0}", 0.45f, SuccessMain, textAlign = TextAlign.Center)
            MonthlyTableCell("${line.lateDays ?: 0}", 0.45f, LmsColors.Sage, textAlign = TextAlign.Center)
            MonthlyTableCell("${line.excusedDays ?: 0}", 0.45f, TextSubtitle, textAlign = TextAlign.Center)
            MonthlyTableCell(
                "$absent",
                0.55f,
                if (absent > 0) ErrorMain else PrimaryDark,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center,
            )
            MonthlyTableCell(formatAttendanceByDay(line.attendanceByDay), 1.5f, TextSubtitle)
            MonthlyTableCell(line.remarks.orEmpty().ifBlank { "—" }, 1.0f, TextSubtitle)
        }
    }
}

private fun formatAttendanceByDay(attendanceByDay: Map<String, String>?): String {
    if (attendanceByDay.isNullOrEmpty()) return "—"

    return attendanceByDay
        .toList()
        .sortedBy { it.first.toIntOrNull() ?: Int.MAX_VALUE }
        .joinToString(" ") { (day, status) ->
            val marker = when (status.lowercase(Locale.ROOT)) {
                "present" -> "P"
                "late" -> "L"
                "excused" -> "E"
                "absent" -> "A"
                else -> status.take(1).uppercase(Locale.ROOT)
            }
            "${day}:${marker}"
        }
}

@Composable
private fun RowScope.MonthlyTableCell(
    text: String,
    weight: Float,
    color: Color,
    fontWeight: FontWeight = FontWeight.Normal,
    textAlign: TextAlign = TextAlign.Start,
) {
    Text(
        text,
        Modifier.weight(weight),
        fontSize = 11.sp,
        color = color,
        fontWeight = fontWeight,
        textAlign = textAlign,
        maxLines = 2,
        overflow = TextOverflow.Ellipsis,
    )
}

@Composable
private fun StatChip(label: String, value: String, valueColor: Color = PrimaryDark) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(label, fontSize = 10.sp, color = TextSubtitle)
        Text(value, fontWeight = FontWeight.Bold, color = valueColor, fontSize = 14.sp)
    }
}
