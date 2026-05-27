package com.bnhs.edutrack.reports

import android.content.Context
import android.content.Intent
import androidx.core.content.FileProvider
import com.bnhs.edutrack.network.MonthlyReportDetailDto
import java.io.File

object MonthlyReportExcelExporter {
    fun buildXlsHtml(report: MonthlyReportDetailDto): String {
        val section = report.section?.name.orEmpty()
        val schoolYear = report.schoolYear?.name.orEmpty()
        val period = report.periodLabel.orEmpty()
        val totalAbsent = report.totalAbsentDays ?: 0

        val rows = buildString {
            report.lines.orEmpty().forEachIndexed { index, line ->
                val remarksEscaped = escapeHtml(line.remarks.orEmpty().replace("\r\n", "\n")).replace("\n", "<br/>")
                appendLine(
                    "<tr>" +
                        "<td>${index + 1}</td>" +
                        "<td>${escapeHtml(line.studentName.orEmpty())}</td>" +
                        "<td>${escapeHtml(line.lrn.orEmpty())}</td>" +
                        "<td>${line.schoolDays ?: 0}</td>" +
                        "<td>${line.presentDays ?: 0}</td>" +
                        "<td>${line.lateDays ?: 0}</td>" +
                        "<td>${line.excusedDays ?: 0}</td>" +
                        "<td>${line.absentDays ?: 0}</td>" +
                        "<td>$remarksEscaped</td>" +
                        "</tr>",
                )
            }
        }

        return """
            <!DOCTYPE html>
            <html lang="en">
            <head><meta charset="UTF-8"/></head>
            <body>
            <table border="1" cellpadding="4" cellspacing="0">
                <thead>
                    <tr><th colspan="9">Monthly Attendance Report</th></tr>
                    <tr>
                        <td colspan="9">
                            <strong>Period:</strong> ${escapeHtml(period)} &nbsp;|&nbsp;
                            <strong>Section:</strong> ${escapeHtml(section)} &nbsp;|&nbsp;
                            <strong>School Year:</strong> ${escapeHtml(schoolYear)}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="9">
                            <strong>School Days (section):</strong> ${report.schoolDaysTotal ?: 0} &nbsp;|&nbsp;
                            <strong>Total Absences:</strong> $totalAbsent
                        </td>
                    </tr>
                    <tr>
                        <th>#</th><th>Student</th><th>LRN</th><th>School Days</th><th>Present</th>
                        <th>Late</th><th>Excused</th><th>Absent</th><th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    $rows
                </tbody>
            </table>
            </body>
            </html>
        """.trimIndent()
    }

    fun writeToCache(context: Context, report: MonthlyReportDetailDto): File {
        val safePeriod = (report.periodLabel ?: "report").replace(Regex("[^A-Za-z0-9]+"), "_").trim('_')
        val fileName = "attendance_${safePeriod}_${report.id ?: 0}.xls"
        val dir = File(context.cacheDir, "exports").apply { mkdirs() }
        val file = File(dir, fileName)
        file.writeText(buildXlsHtml(report))
        return file
    }

    fun shareExcel(context: Context, report: MonthlyReportDetailDto): Intent {
        val file = writeToCache(context, report)
        val uri = FileProvider.getUriForFile(context, "${context.packageName}.fileprovider", file)
        return Intent(Intent.ACTION_SEND).apply {
            type = "application/vnd.ms-excel"
            putExtra(Intent.EXTRA_STREAM, uri)
            putExtra(Intent.EXTRA_SUBJECT, "Monthly Attendance - ${report.periodLabel.orEmpty()}")
            putExtra(Intent.EXTRA_TEXT, "Monthly attendance report for ${report.section?.name.orEmpty()} (${report.periodLabel.orEmpty()}).")
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }
    }

    private fun escapeHtml(value: String): String {
        return value
            .replace("&", "&amp;")
            .replace("<", "&lt;")
            .replace(">", "&gt;")
            .replace("\"", "&quot;")
            .replace("'", "&#39;")
    }
}
