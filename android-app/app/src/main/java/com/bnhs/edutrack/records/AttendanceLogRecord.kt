package com.bnhs.edutrack.records

import com.bnhs.edutrack.AttendanceRecord
import java.time.LocalDate
import java.time.LocalDateTime

/** Gate access log (Security CRUD). */
data class GateLogRecord(
    val id: Long = 0L,
    val studentId: Long,
    val studentName: String,
    val lrn: String,
    val rfidUid: String,
    val date: LocalDate,
    val loggedAt: LocalDateTime,
    val status: String,
)

data class GateLogInput(
    val id: Long = 0L,
    val studentId: Long = 0L,
    val date: LocalDate = LocalDate.now(),
    val status: String = "PRESENT",
)

/** Class attendance log (Adviser CRUD). */
data class AdviserLogRecord(
    val id: Long = 0L,
    val studentId: Long,
    val studentName: String,
    val lrn: String,
    val date: LocalDate,
    val loggedAt: LocalDateTime,
    val status: String,
    val parentName: String,
    val parentContact: String,
)

data class AdviserLogInput(
    val id: Long = 0L,
    val studentId: Long = 0L,
    val date: LocalDate = LocalDate.now(),
    val status: String = "PRESENT",
    val parentName: String = "",
    val parentContact: String = "",
)

data class AttendanceLogFilter(
    val date: String = "",
    val status: String = "",
    val query: String = "",
)

fun AttendanceRecord.matchesRole(loggedBy: String): Boolean =
    this.loggedBy.equals(loggedBy, ignoreCase = true) ||
        (loggedBy == "ADVISER" && this.loggedBy.equals("TEACHER", ignoreCase = true))
