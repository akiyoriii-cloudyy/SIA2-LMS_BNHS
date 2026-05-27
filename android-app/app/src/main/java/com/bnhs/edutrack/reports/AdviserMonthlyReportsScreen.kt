package com.bnhs.edutrack.reports

import com.bnhs.edutrack.ui.*

import android.content.Intent
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.animateContentSize
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.ExpandLess
import androidx.compose.material.icons.filled.ExpandMore
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.TableChart
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
import java.time.Year
import java.time.YearMonth
import java.time.ZoneId
import java.time.format.TextStyle
import java.util.Locale

private val PHILIPPINES_ZONE: ZoneId = ZoneId.of("Asia/Manila")

object MonthlyReportPeriod {
    val allMonths: List<Pair<Int, String>> = (1..12).map { month ->
        month to java.time.Month.of(month)
            .getDisplayName(TextStyle.FULL, Locale.ENGLISH)
    }

    fun monthLabel(month: Int): String =
        java.time.Month.of(month.coerceIn(1, 12))
            .getDisplayName(TextStyle.FULL, Locale.ENGLISH)

    fun defaultYear(): Int = YearMonth.now(PHILIPPINES_ZONE).year

    fun defaultMonth(): Int = YearMonth.now(PHILIPPINES_ZONE).monthValue

    fun yearOptions(reports: List<MonthlyReportSummaryDto>): List<Int> {
        val current = defaultYear()
        val fromReports = reports.mapNotNull { it.reportYear }
        return ((current - 3)..(current + 1))
            .toList()
            .union(fromReports)
            .sortedDescending()
    }
}

class MonthlyReportsViewModel(
    private val repository: MonthlyReportsRepository,
) : ViewModel() {
    var reports by mutableStateOf<List<MonthlyReportSummaryDto>>(emptyList())
    var selectedReportId by mutableStateOf<Long?>(null)
    var selectedReport by mutableStateOf<MonthlyReportDetailDto?>(null)
    var selectedMonth by mutableStateOf(MonthlyReportPeriod.defaultMonth())
    var selectedYear by mutableStateOf(MonthlyReportPeriod.defaultYear())
    var isLoading by mutableStateOf(false)
    var isGenerating by mutableStateOf(false)
    var statusMessage by mutableStateOf("Select month and year, then generate the report for the web portal.")

    fun refreshList(selectLatest: Boolean = true) {
        viewModelScope.launch {
            isLoading = true
            when (val result = repository.listReports()) {
                is ReportsResult.Success -> {
                    reports = result.value
                    if (reports.isEmpty()) {
                        selectedReportId = null
                        selectedReport = null
                        statusMessage = "No monthly report yet. Mark daily attendance in Roster, then tap Generate Excel Reports Attendance."
                    } else {
                        if (selectLatest) {
                            val latest = reports.first()
                            selectedMonth = latest.reportMonth ?: MonthlyReportPeriod.defaultMonth()
                            selectedYear = latest.reportYear ?: MonthlyReportPeriod.defaultYear()
                        }
                        applyMonthYearSelection()
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

    fun onMonthSelected(month: Int) {
        if (selectedMonth == month) return
        selectedMonth = month
        applyMonthYearSelection()
    }

    fun onYearSelected(year: Int) {
        if (selectedYear == year) return
        selectedYear = year
        applyMonthYearSelection()
    }

    private fun applyMonthYearSelection() {
        val month = selectedMonth
        val year = selectedYear
        val match = reports.firstOrNull { r ->
            r.reportMonth == month && r.reportYear == year
        }
        if (match?.id != null) {
            loadDetail(match.id)
        } else {
            selectedReportId = null
            selectedReport = null
            statusMessage = "No report for ${MonthlyReportPeriod.monthLabel(month)} $year. Tap Generate Excel Reports Attendance to publish it on the web portal."
        }
    }

    fun generateExcelReport() {
        viewModelScope.launch {
            isGenerating = true
            when (val result = repository.generateReport(selectedYear, selectedMonth)) {
                is ReportsResult.Success -> {
                    val (message, _) = result.value
                    statusMessage = message
                    when (val listResult = repository.listReports()) {
                        is ReportsResult.Success -> {
                            reports = listResult.value
                            val match = reports.firstOrNull { r ->
                                r.reportMonth == selectedMonth && r.reportYear == selectedYear
                            }
                            if (match?.id != null) {
                                loadDetail(match.id)
                            } else {
                                applyMonthYearSelection()
                            }
                        }
                        is ReportsResult.Error -> {
                            statusMessage = listResult.message
                        }
                    }
                }
                is ReportsResult.Error -> {
                    statusMessage = result.message
                }
            }
            isGenerating = false
        }
    }

    fun loadDetail(reportId: Long) {
        selectedReportId = reportId
        viewModelScope.launch {
            isLoading = true
            when (val result = repository.getReport(reportId)) {
                is ReportsResult.Success -> {
                    selectedReport = result.value
                    result.value.reportMonth?.let { selectedMonth = it }
                    result.value.reportYear?.let { selectedYear = it }
                    val month = MonthlyReportPeriod.monthLabel(selectedMonth)
                    statusMessage = "$month $selectedYear — ${result.value.lines.orEmpty().size} students"
                }
                is ReportsResult.Error -> {
                    selectedReport = null
                    statusMessage = result.message
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
    val yearOptions = remember(viewModel.reports) {
        MonthlyReportPeriod.yearOptions(viewModel.reports)
    }

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
                    report?.section?.name?.takeIf { it.isNotBlank() }
                        ?: "Pick month and year below",
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

        Row(
            Modifier
                .fillMaxWidth()
                .padding(vertical = 6.dp),
            horizontalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            MonthYearDropdown(
                label = "Month",
                value = MonthlyReportPeriod.monthLabel(viewModel.selectedMonth),
                options = MonthlyReportPeriod.allMonths.map { it.second },
                onSelect = { label ->
                    MonthlyReportPeriod.allMonths
                        .firstOrNull { it.second == label }
                        ?.first
                        ?.let { viewModel.onMonthSelected(it) }
                },
                modifier = Modifier.weight(1f),
                enabled = !viewModel.isLoading,
            )
            MonthYearDropdown(
                label = "Year",
                value = viewModel.selectedYear.toString(),
                options = yearOptions.map { it.toString() },
                onSelect = { yearStr ->
                    yearStr.toIntOrNull()?.let { viewModel.onYearSelected(it) }
                },
                modifier = Modifier.weight(1f),
                enabled = !viewModel.isLoading,
            )
        }

        Row(
            Modifier.fillMaxWidth().padding(bottom = 8.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            Button(
                onClick = {
                    report?.let { r ->
                        context.startActivity(
                            Intent.createChooser(
                                MonthlyReportExcelExporter.shareExcel(context, r),
                                "Send report",
                            ),
                        )
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
        }

        Button(
            onClick = { viewModel.generateExcelReport() },
            enabled = !viewModel.isLoading && !viewModel.isGenerating,
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 8.dp),
            shape = RoundedCornerShape(12.dp),
            colors = ButtonDefaults.buttonColors(containerColor = PrimaryMain),
        ) {
            if (viewModel.isGenerating) {
                CircularProgressIndicator(
                    modifier = Modifier.size(18.dp),
                    color = Color.White,
                    strokeWidth = 2.dp,
                )
            } else {
                Icon(Icons.Default.TableChart, null, modifier = Modifier.size(18.dp))
            }
            Spacer(Modifier.width(8.dp))
            Text(
                if (viewModel.isGenerating) "Generating…" else "Generate Excel Reports Attendance",
                fontSize = 12.sp,
            )
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
            var reportExpanded by remember(report?.id) { mutableStateOf(true) }

            Card(
                Modifier
                    .fillMaxWidth()
                    .animateContentSize(),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Row(
                    Modifier
                        .fillMaxWidth()
                        .clickable { reportExpanded = !reportExpanded }
                        .padding(horizontal = 14.dp, vertical = 12.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Column(Modifier.weight(1f)) {
                        Text(
                            "Student attendance",
                            fontWeight = FontWeight.SemiBold,
                            color = PrimaryDark,
                            fontSize = 14.sp,
                        )
                        Text(
                            "${MonthlyReportPeriod.monthLabel(viewModel.selectedMonth)} ${viewModel.selectedYear} · ${lines.size} students",
                            fontSize = 11.sp,
                            color = TextSubtitle,
                        )
                    }
                    Icon(
                        imageVector = if (reportExpanded) Icons.Default.ExpandLess else Icons.Default.ExpandMore,
                        contentDescription = if (reportExpanded) "Roll up" else "Roll down",
                        tint = PrimaryMain,
                    )
                }
            }

            AnimatedVisibility(
                visible = reportExpanded,
                modifier = Modifier.weight(1f),
            ) {
                Column(Modifier.fillMaxSize()) {
                    Card(
                        Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = Color.White),
                    ) {
                        Row(
                            Modifier
                                .fillMaxWidth()
                                .padding(12.dp),
                            horizontalArrangement = Arrangement.SpaceEvenly,
                        ) {
                            StatChip("Month", MonthlyReportPeriod.monthLabel(viewModel.selectedMonth))
                            StatChip("Year", "${viewModel.selectedYear}")
                            StatChip("Students", "${lines.size}")
                            StatChip("Total absent", "${report.totalAbsentDays ?: 0}", ErrorMain)
                        }
                    }

                    if (viewModel.isLoading) {
                        LinearProgressIndicator(
                            Modifier.fillMaxWidth().padding(top = 6.dp),
                            color = PrimaryMain,
                        )
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
                        MonthlyTableHeaderCell("Remarks", 1.1f)
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
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun MonthYearDropdown(
    label: String,
    value: String,
    options: List<String>,
    onSelect: (String) -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
) {
    var expanded by remember { mutableStateOf(false) }
    ExposedDropdownMenuBox(
        expanded = expanded,
        onExpandedChange = { if (enabled) expanded = it },
        modifier = modifier,
    ) {
        OutlinedTextField(
            value = value,
            onValueChange = {},
            readOnly = true,
            enabled = enabled,
            modifier = Modifier.menuAnchor().fillMaxWidth(),
            label = { Text(label) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded) },
            shape = RoundedCornerShape(12.dp),
        )
        ExposedDropdownMenu(
            expanded = expanded,
            onDismissRequest = { expanded = false },
            modifier = Modifier.heightIn(max = 320.dp),
        ) {
            options.forEach { option ->
                DropdownMenuItem(
                    text = { Text(option, fontSize = 13.sp) },
                    onClick = {
                        expanded = false
                        onSelect(option)
                    },
                )
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
            MonthlyTableCell(line.remarks.orEmpty().ifBlank { "—" }, 1.1f, TextSubtitle)
        }
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
