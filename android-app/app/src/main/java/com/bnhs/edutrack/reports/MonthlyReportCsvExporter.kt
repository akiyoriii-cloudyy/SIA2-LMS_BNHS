package com.bnhs.edutrack.reports

import android.content.Context
import android.content.Intent
import androidx.core.content.FileProvider
import com.bnhs.edutrack.network.MonthlyReportDetailDto
import com.bnhs.edutrack.network.MonthlyReportLineDto
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

object MonthlyReportCsvExporter {

    fun buildCsv(report: MonthlyReportDetailDto): String {
        val section = report.section?.name.orEmpty()
        val schoolYear = report.schoolYear?.name.orEmpty()
        val period = report.periodLabel.orEmpty()
        val lines = report.lines.orEmpty()

        val sb = StringBuilder()
        sb.appendLine("Monthly Attendance Report")
        sb.appendLine("Period,$period")
        sb.appendLine("Section,$section")
        sb.appendLine("School Year,$schoolYear")
        sb.appendLine("School Days (section),${report.schoolDaysTotal ?: 0}")
        sb.appendLine()
        sb.appendLine(csvRow(listOf("#", "Student", "LRN", "School Days", "Present", "Late", "Excused", "Absent", "Remarks")))
        lines.forEachIndexed { index, line ->
            sb.appendLine(csvRow(lineToRow(index + 1, line)))
        }
        return sb.toString()
    }

    fun writeToCache(context: Context, report: MonthlyReportDetailDto): File {
        val safePeriod = (report.periodLabel ?: "report")
            .replace(Regex("[^A-Za-z0-9]+"), "_")
            .trim('_')
        val fileName = "attendance_${safePeriod}_${report.id ?: 0}.csv"
        val dir = File(context.cacheDir, "exports").apply { mkdirs() }
        val file = File(dir, fileName)
        file.writeText(buildCsv(report))
        return file
    }

    fun shareCsv(context: Context, report: MonthlyReportDetailDto): Intent {
        val file = writeToCache(context, report)
        val uri = FileProvider.getUriForFile(
            context,
            "${context.packageName}.fileprovider",
            file,
        )
        return Intent(Intent.ACTION_SEND).apply {
            type = "text/csv"
            putExtra(Intent.EXTRA_STREAM, uri)
            putExtra(Intent.EXTRA_SUBJECT, "Monthly Attendance — ${report.periodLabel.orEmpty()}")
            putExtra(
                Intent.EXTRA_TEXT,
                "Monthly attendance report for ${report.section?.name.orEmpty()} (${report.periodLabel.orEmpty()}).",
            )
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }
    }

    fun downloadLabel(report: MonthlyReportDetailDto): String {
        val stamp = SimpleDateFormat("yyyyMMdd_HHmm", Locale.US).format(Date())
        return "attendance_${report.id ?: 0}_$stamp.csv"
    }

    private fun lineToRow(index: Int, line: MonthlyReportLineDto): List<String> = listOf(
        index.toString(),
        line.studentName.orEmpty(),
        line.lrn.orEmpty(),
        (line.schoolDays ?: 0).toString(),
        (line.presentDays ?: 0).toString(),
        (line.lateDays ?: 0).toString(),
        (line.excusedDays ?: 0).toString(),
        (line.absentDays ?: 0).toString(),
        line.remarks.orEmpty(),
    )

    private fun csvRow(cells: List<String>): String =
        cells.joinToString(",") { cell ->
            val escaped = cell.replace("\"", "\"\"")
            if (escaped.contains(',') || escaped.contains('"') || escaped.contains('\n')) {
                "\"$escaped\""
            } else {
                escaped
            }
        }
}
