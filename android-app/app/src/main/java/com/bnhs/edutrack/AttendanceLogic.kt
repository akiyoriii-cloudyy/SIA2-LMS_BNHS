package com.bnhs.edutrack

import java.time.LocalDate
import java.time.LocalDateTime

fun upsertAttendanceRecord(
    records: MutableList<AttendanceRecord>,
    studentId: Int,
    date: LocalDate,
    loggedAt: LocalDateTime,
    status: String,
    loggedBy: String,
) {
    records.removeAll { it.studentId == studentId && it.date == date }
    records.add(
        AttendanceRecord(
            studentId = studentId,
            date = date,
            loggedAt = loggedAt,
            status = status,
            loggedBy = loggedBy,
        ),
    )
}

fun ensureDailyAbsenceDefaults(
    students: List<Student>,
    records: MutableList<AttendanceRecord>,
    date: LocalDate,
    now: LocalDateTime,
) {
    students.forEach { s ->
        if (decisiveRecordForDay(records, s.id, date) == null) {
            records.add(
                AttendanceRecord(
                    studentId = s.id,
                    date = date,
                    loggedAt = now,
                    status = "ABSENT",
                    loggedBy = "SYSTEM",
                ),
            )
        }
    }
}

fun decisiveRecordForDay(records: List<AttendanceRecord>, studentId: Int, date: LocalDate): AttendanceRecord? =
    records.filter { it.studentId == studentId && it.date == date }.maxByOrNull { it.loggedAt }

fun consecutiveAbsentCalendarDaysEndingOn(
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
        } else {
            break
        }
    }
    return streak
}
