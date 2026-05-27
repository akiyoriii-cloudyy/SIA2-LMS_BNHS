package com.bnhs.edutrack

import java.time.LocalDate
import java.time.LocalDateTime

data class Student(
    val id: Int,
    val name: String,
    val lrn: String,
    val rfidUid: String,
    val parentName: String,
    val parentContact: String,
    val enrollmentId: Long? = null,
)

data class AttendanceRecord(
    val studentId: Int,
    val date: LocalDate,
    val loggedAt: LocalDateTime,
    val status: String,
    val loggedBy: String,
)

enum class AdminTab { OVERVIEW, RECORDS, SYSTEM }

enum class SecurityTab { SCAN, RECORDS, SCAN_LOG }

enum class AdviserTab { ROSTER, RECORDS, REPORTS, HISTORY, ALERTS, PARENTS }
