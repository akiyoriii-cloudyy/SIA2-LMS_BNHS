package com.bnhs.edutrack.reports

import android.content.Context
import android.content.Intent
import androidx.core.content.FileProvider
import com.bnhs.edutrack.network.MonthlyReportDetailDto
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

/**
 * Creates an Excel-compatible legacy `.xls` file (HTML table).
 * Excel can open it directly, without adding heavy XLSX libraries.
 */
object MonthlyReportExcelExporter {

    fun buildXlsHtml(report: MonthlyReportDetailDto): String {
        val section = report.section?.name.orEmpty()
        val schoolYear = report.schoolYear?.name.orEmpty()
        val period = report.periodLabel.orEmpty()
        val safeTotalAbsent = report.totalAbsentDays ?: 0
        val lines = report.lines.orEmpty()

        val headerRows = buildString {
            appendLine("<tr><th colspan=\"9\">Monthly Attendance Report</th></tr>")
            appendLine(
                "<tr><td colspan=\"9\">" +
                    "<strong>Period:</strong> ${escapeHtml(period)}" +
                    " &nbsp;|&nbsp; <strong>Section:</strong> ${escapeHtml(section)}" +
                    " &nbsp;|&nbsp; <strong>School Year:</strong> ${escapeHtml(schoolYear)}" +
                    "</td></tr>",
            )
            appendLine(
                "<tr><td colspan=\"9\">" +
                    "<strong>School Days (section):</strong> ${report.schoolDaysTotal ?: 0}" +
                    " &nbsp;|&nbsp; <strong>Total Absences:</strong> ${safeTotalAbsent}" +
                    "</td></tr>",
            )
            appendLine(
                """
                <tr>
                  <th>#</th>
                  <th>Student</th>
                  <th>LRN</th>
                  <th>School Days</th>
                  <th>Present</th>
                  <th>Late</th>
                  <th>Excused</th>
                  <th>Absent</th>
                  <th>Remarks</th>
                </tr>
                """.trimIndent(),
            )
        }

        val bodyRows = buildString {
            lines.forEachIndexed { index, line ->
                val remarksText = line.remarks.orEmpty().replace("\r\n", "\n")
                val remarksEscaped = escapeHtml(remarksText)
                // Re-introduce line breaks as <br/> tags after escaping.
                val remarksHtml = remarksEscaped.replace("\n", "<br/>")

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
                        "<td>${remarksHtml}</td>" +
                        "</tr>",
                )
            }
        }

        return buildString {
            appendLine("<!DOCTYPE html>")
            appendLine("<html lang=\"en\"><head><meta charset=\"UTF-8\"/></head><body>")
            appendLine("<table border=\"1\" cellpadding=\"4\" cellspacing=\"0\">")
            appendLine(headerRows)
            appendLine(bodyRows)
            appendLine("</table>")
            appendLine("</body></html>")
        }
    }

    fun writeToCache(context: Context, report: MonthlyReportDetailDto): File {
        val safePeriod = (report.periodLabel ?: "report")
            .replace(Regex("[^A-Za-z0-9]+"), "_")
            .trim('_')
        val fileName = "attendance_${safePeriod}_${report.id ?: 0}.xls"
        val dir = File(context.cacheDir, "exports").apply { mkdirs() }
        val file = File(dir, fileName)
        file.writeText(buildXlsHtml(report))
        return file
    }

    fun shareExcel(context: Context, report: MonthlyReportDetailDto): Intent {
        val file = writeToCache(context, report)
        val uri = FileProvider.getUriForFile(
            context,
            "${context.packageName}.fileprovider",
            file,
        )

        return Intent(Intent.ACTION_SEND).apply {
            type = "application/vnd.ms-excel"
            putExtra(Intent.EXTRA_STREAM, uri)
            putExtra(Intent.EXTRA_SUBJECT, "Monthly Attendance — ${report.periodLabel.orEmpty()}")
            putExtra(
                Intent.EXTRA_TEXT,
                "Monthly attendance Excel report for ${report.section?.name.orEmpty()} (${report.periodLabel.orEmpty()}).",
            )
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }
    }

    fun downloadLabel(report: MonthlyReportDetailDto): String {
        val stamp = SimpleDateFormat("yyyyMMdd_HHmm", Locale.US).format(Date())
        return "attendance_${report.id ?: 0}_$stamp.xls"
    }

    private fun escapeHtml(s: String): String {
        // Very small HTML escaper (no external deps).
        return s
            .replace("&", "&amp;")
            .replace("<", "&lt;")
            .replace(">", "&gt;")
            .replace("\"", "&quot;")
            .replace("'", "&#39;")
    }
}

