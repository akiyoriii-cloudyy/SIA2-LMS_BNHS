package com.bnhs.edutrack.data

import android.content.Context
import com.bnhs.edutrack.auth.SessionStore
import com.bnhs.edutrack.security.SensitiveDataGuard
import com.bnhs.edutrack.tracking.ActivityAction
import com.bnhs.edutrack.tracking.ActivityCategory
import com.bnhs.edutrack.tracking.ActivityLogger
import com.bnhs.edutrack.transaction.BusinessTransactionManager
import com.bnhs.edutrack.transaction.TxEntityType
import com.bnhs.edutrack.transaction.TxOperation
import java.time.LocalDate
import java.time.LocalDateTime
import java.time.format.DateTimeFormatter

/**
 * High-level facade around [BnhsDatabase].
 *
 * The repository keeps the UI insulated from Room types — it returns simple
 * value classes that the Compose code already uses, while transparently
 * persisting every change to SQLite for backup.
 */
class BnhsRepository private constructor(
    private val context: Context,
    private val db: BnhsDatabase,
    private val guard: SensitiveDataGuard,
    private val txManager: BusinessTransactionManager,
) {

    // ---------------------------------------------------------------- value objects

    /** Flat representation of a student + their primary guardian, used by the UI. */
    data class StudentWithParent(
        val id: Long,
        val name: String,
        val lrn: String,
        val rfidUid: String,
        val parentId: Long,
        val parentName: String,
        val parentContact: String,
    )

    data class AttendanceLog(
        val studentId: Long,
        val date: LocalDate,
        val loggedAt: LocalDateTime,
        val status: String,
        val loggedBy: String,
    )

    data class StoredAccount(
        val id: Long,
        val username: String,
        val role: String,
        val displayName: String,
        val assignment: String,
    )

    // ---------------------------------------------------------------- seeding

    /**
     * Populate the database on first launch with the same defaults that the
     * app used to hard-code. Safe to call every startup — it no-ops once data
     * already exists.
     */
    suspend fun ensureSeedData() {
        val hasServerSession = SessionStore(context).loadSession() != null
        if (!hasServerSession && db.studentDao().count() == 0) {
            seedStudentsAndParents()
        }
        if (db.userAccountDao().count() == 0) {
            seedUserAccounts()
        }
    }

    private suspend fun seedStudentsAndParents() {
        val seedData = listOf(
            Triple(
                StudentEntity(
                    name = "Santos, Ana",
                    lrn = "1111110001",
                    rfidUid = "RFID-ANA-001",
                    gradeLevel = "10",
                    section = "Diamond",
                ),
                "Santos, Maria",
                "09943621529",
            ),
            Triple(
                StudentEntity(
                    name = "Cruz, Bryan",
                    lrn = "1111110002",
                    rfidUid = "RFID-BRY-002",
                    gradeLevel = "10",
                    section = "Diamond",
                ),
                "Cruz, Ricardo",
                "09178234561",
            ),
            Triple(
                StudentEntity(
                    name = "Dela Cruz, Ivan",
                    lrn = "1111110003",
                    rfidUid = "RFID-IVA-003",
                    gradeLevel = "10",
                    section = "Diamond",
                ),
                "Dela Cruz, Nora",
                "09663549820",
            ),
        )
        seedData.forEach { (student, parentName, parentContact) ->
            val studentId = db.studentDao().insert(student)
            if (studentId > 0L) {
                db.parentDao().insert(
                    guard.parentForStorage(
                        ParentEntity(
                            studentId = studentId,
                            name = parentName,
                            contact = parentContact,
                            relationship = "Guardian",
                            isPrimary = true,
                        ),
                    ),
                )
            }
        }
    }

    private suspend fun seedUserAccounts() {
        val seed = listOf(
            guard.userForStorage("security", "password", "SECURITY_GUARD", "Officer Reyes", "Main Gate Post"),
            guard.userForStorage("guard", "password", "SECURITY_GUARD", "Officer Cruz", "Side Gate Post"),
            guard.userForStorage("adviser", "password", "TEACHER", "Ms. Dela Rosa", "Grade 10 - Adviser"),
            guard.userForStorage("teacher", "password", "TEACHER", "Mr. Manalo", "Grade 9 - Adviser"),
        )
        seed.forEach { db.userAccountDao().insert(it) }
    }

    // ---------------------------------------------------------------- queries

    suspend fun loadStudentsWithParent(): List<StudentWithParent> {
        return db.studentDao().getAll().map { student ->
            val parent = db.parentDao().primaryForStudent(student.id)?.let { guard.parentForDisplay(it) }
            StudentWithParent(
                id = student.id,
                name = student.name,
                lrn = student.lrn,
                rfidUid = student.rfidUid,
                parentId = parent?.id ?: 0L,
                parentName = parent?.name.orEmpty(),
                parentContact = parent?.contact.orEmpty(),
            )
        }
    }

    suspend fun loadAttendanceLogs(): List<AttendanceLog> {
        return db.attendanceDao().getAll().mapNotNull { row ->
            runCatching {
                AttendanceLog(
                    studentId = row.studentId,
                    date = LocalDate.parse(row.date),
                    loggedAt = LocalDateTime.parse(row.loggedAt),
                    status = row.status,
                    loggedBy = row.loggedBy,
                )
            }.getOrNull()
        }
    }

    // ---------------------------------------------------------------- mutations

    suspend fun authenticate(username: String, password: String, role: String): StoredAccount? {
        val match = db.userAccountDao().findByUsernameAndRole(username.trim(), role) ?: return null
        if (!guard.verifyPassword(match, password)) return null
        db.userAccountDao().touchLastLogin(match.id, System.currentTimeMillis())
        return StoredAccount(
            id = match.id,
            username = match.username,
            role = match.role,
            displayName = match.displayName,
            assignment = match.assignment,
        )
    }

    suspend fun upsertAttendance(
        studentId: Long,
        date: LocalDate,
        loggedAt: LocalDateTime,
        status: String,
        loggedBy: String,
    ) {
        // We use REPLACE strategy plus a unique (student_id, date) index, so the
        // most recent log overwrites the previous one for the same school day.
        db.attendanceDao().deleteForStudentOnDate(studentId, ISO_DATE.format(date), loggedBy)
        db.attendanceDao().insert(
            AttendanceEntity(
                studentId = studentId,
                date = ISO_DATE.format(date),
                loggedAt = ISO_DATETIME.format(loggedAt),
                status = status,
                loggedBy = loggedBy,
            )
        )
    }

    suspend fun clearAttendanceHistory() {
        db.attendanceDao().deleteAll()
        db.alertLogDao().deleteAll()
    }

    suspend fun updateGuardianContact(
        studentId: Long,
        parentName: String,
        parentContact: String,
    ) {
        val result = txManager.execute(
            operation = TxOperation.GUARDIAN_UPDATE,
            entityType = TxEntityType.PARENT,
            actorEmail = "",
            summary = "Guardian updated for student #$studentId",
            entityId = studentId,
        ) {
            val existing = db.parentDao().primaryForStudent(studentId)
            if (existing == null) {
                db.parentDao().insert(
                    guard.parentForStorage(
                        ParentEntity(
                            studentId = studentId,
                            name = parentName,
                            contact = parentContact,
                            isPrimary = true,
                        ),
                    ),
                )
            } else {
                db.parentDao().updateContactInfo(
                    id = existing.id,
                    name = parentName,
                    contact = guard.encryptField(parentContact),
                )
            }
        }
        if (result is com.bnhs.edutrack.records.RecordsResult.Success) {
            ActivityLogger.get(context).log(
                category = ActivityCategory.ACCOUNT,
                action = ActivityAction.GUARDIAN_UPDATE,
                success = true,
                actorEmail = null,
                details = "Guardian updated for student #$studentId",
                sessionUuid = SessionStore(context).getTrackingSessionUuid(),
            )
        }
    }

    suspend fun logAlert(
        studentId: Long,
        alertType: String,
        message: String,
        recipientName: String,
        recipientContact: String,
        status: String,
        errorDetail: String? = null,
    ) {
        db.alertLogDao().insert(
            AlertLogEntity(
                studentId = studentId,
                alertType = alertType,
                message = message,
                recipientName = recipientName,
                recipientContact = guard.encryptField(recipientContact),
                status = status,
                errorDetail = errorDetail,
            )
        )
    }

    companion object {
        private val ISO_DATE: DateTimeFormatter = DateTimeFormatter.ISO_LOCAL_DATE
        private val ISO_DATETIME: DateTimeFormatter = DateTimeFormatter.ISO_LOCAL_DATE_TIME

        @Volatile
        private var INSTANCE: BnhsRepository? = null

        fun get(context: Context): BnhsRepository {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: BnhsRepository(
                    context.applicationContext,
                    BnhsDatabase.get(context),
                    SensitiveDataGuard.get(context),
                    BusinessTransactionManager.get(context),
                ).also { INSTANCE = it }
            }
        }
    }
}
