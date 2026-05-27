package com.bnhs.edutrack.records

import android.content.Context
import com.bnhs.edutrack.AttendanceRecord
import com.bnhs.edutrack.Student
import com.bnhs.edutrack.data.AttendanceEntity
import com.bnhs.edutrack.data.BnhsDatabase
import com.bnhs.edutrack.data.ParentEntity
import com.bnhs.edutrack.data.RecordAuditLogEntity
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

class AttendanceRecordsRepository private constructor(
    private val context: Context,
    private val db: BnhsDatabase,
    private val guard: SensitiveDataGuard,
    private val txManager: BusinessTransactionManager,
) {

    private val isoDate: DateTimeFormatter = DateTimeFormatter.ISO_LOCAL_DATE
    private val isoDateTime: DateTimeFormatter = DateTimeFormatter.ISO_LOCAL_DATE_TIME

    suspend fun loadAppAttendance(): List<AttendanceRecord> =
        db.attendanceDao().getAll().mapNotNull { entityToApp(it) }

    suspend fun upsertAppRecord(record: AttendanceRecord, actorEmail: String): String? {
        db.attendanceDao().deleteForStudentOnDate(record.studentId.toLong(), isoDate.format(record.date), record.loggedBy)
        val id = db.attendanceDao().insert(
            AttendanceEntity(
                studentId = record.studentId.toLong(),
                date = isoDate.format(record.date),
                loggedAt = isoDateTime.format(record.loggedAt),
                status = record.status,
                loggedBy = record.loggedBy,
            ),
        )
        audit("UPSERT", id, actorEmail, "${record.loggedBy} ${record.status} student#${record.studentId}")

        return when (val syncResult = AttendanceSyncRepository.get(context).syncAttendanceRecord(record, record.studentId)) {
            is AttendanceSyncResult.Success -> null
            is AttendanceSyncResult.Error -> syncResult.message
            AttendanceSyncResult.Skipped ->
                "Saved on device only. Open the app while online so the class list syncs from the server, then mark attendance again."
        }
    }

    // --- Security (GATE) ---

    suspend fun listGateLogs(filter: AttendanceLogFilter): List<GateLogRecord> {
        val students = db.studentDao().getAll().associateBy { it.id }
        return db.attendanceDao().listByRole("GATE", filter.date, filter.status)
            .mapNotNull { entity ->
                val student = students[entity.studentId] ?: return@mapNotNull null
                if (filter.query.isNotBlank()) {
                    val q = filter.query.lowercase()
                    if (!student.name.lowercase().contains(q) &&
                        !student.lrn.lowercase().contains(q) &&
                        !student.rfidUid.lowercase().contains(q)
                    ) {
                        return@mapNotNull null
                    }
                }
                GateLogRecord(
                    id = entity.id,
                    studentId = entity.studentId,
                    studentName = student.name,
                    lrn = student.lrn,
                    rfidUid = student.rfidUid,
                    date = LocalDate.parse(entity.date),
                    loggedAt = LocalDateTime.parse(entity.loggedAt),
                    status = entity.status,
                )
            }
    }

    suspend fun createGateLog(input: GateLogInput, actorEmail: String): RecordsResult<GateLogRecord> {
        if (input.studentId <= 0L) return RecordsResult.Error("Select a student.")
        if (input.status !in GATE_STATUSES) return RecordsResult.Error("Invalid gate status.")
        val student = db.studentDao().getById(input.studentId) ?: return RecordsResult.Error("Student not found.")
        val loggedAt = LocalDateTime.of(input.date, java.time.LocalTime.now())
        val entity = AttendanceEntity(
            studentId = input.studentId,
            date = isoDate.format(input.date),
            loggedAt = isoDateTime.format(loggedAt),
            status = input.status,
            loggedBy = "GATE",
        )
        return txManager.execute(
            operation = TxOperation.ATTENDANCE_GATE_UPSERT,
            entityType = TxEntityType.ATTENDANCE,
            actorEmail = actorEmail,
            summary = "Gate ${input.status} for ${student.name}",
            entityId = input.studentId,
        ) {
            db.attendanceDao().deleteForStudentOnDate(input.studentId, isoDate.format(input.date), "GATE")
            val id = db.attendanceDao().insert(entity)
            audit("CREATE", id, actorEmail, "Gate ${input.status} for ${student.name}")
            GateLogRecord(id, input.studentId, student.name, student.lrn, student.rfidUid, input.date, loggedAt, input.status)
        }
    }

    suspend fun updateGateLog(input: GateLogInput, recordId: Long, actorEmail: String): RecordsResult<GateLogRecord> {
        val existing = db.attendanceDao().getById(recordId) ?: return RecordsResult.Error("Record not found.")
        if (existing.loggedBy != "GATE") return RecordsResult.Error("Not a gate record.")
        if (input.status !in GATE_STATUSES) return RecordsResult.Error("Invalid gate status.")
        val student = db.studentDao().getById(existing.studentId) ?: return RecordsResult.Error("Student not found.")
        val loggedAt = LocalDateTime.parse(existing.loggedAt)
        val updated = existing.copy(status = input.status, date = isoDate.format(input.date))
        return txManager.execute(
            operation = TxOperation.ATTENDANCE_GATE_UPSERT,
            entityType = TxEntityType.ATTENDANCE,
            actorEmail = actorEmail,
            summary = "Gate update for ${student.name}",
            entityId = recordId,
        ) {
            db.attendanceDao().update(updated)
            audit("UPDATE", recordId, actorEmail, "Gate log updated for ${student.name}")
            GateLogRecord(recordId, existing.studentId, student.name, student.lrn, student.rfidUid, input.date, loggedAt, input.status)
        }
    }

    suspend fun deleteGateLog(recordId: Long, actorEmail: String): RecordsResult<Unit> {
        val existing = db.attendanceDao().getById(recordId) ?: return RecordsResult.Error("Record not found.")
        if (existing.loggedBy != "GATE") return RecordsResult.Error("Not a gate record.")
        db.attendanceDao().delete(existing)
        audit("DELETE", recordId, actorEmail, "Gate log deleted #${existing.studentId}")
        return RecordsResult.Success(Unit)
    }

    // --- Adviser ---

    suspend fun listAdviserLogs(filter: AttendanceLogFilter): List<AdviserLogRecord> {
        val students = db.studentDao().getAll().associateBy { it.id }
        val gateExcluded = db.attendanceDao().getAll().filter {
            it.loggedBy == "ADVISER" || it.loggedBy == "TEACHER"
        }
        return gateExcluded.mapNotNull { entity ->
            val student = students[entity.studentId] ?: return@mapNotNull null
            if (filter.date.isNotBlank() && entity.date != filter.date) return@mapNotNull null
            if (filter.status.isNotBlank() && entity.status != filter.status) return@mapNotNull null
            if (filter.query.isNotBlank()) {
                val q = filter.query.lowercase()
                if (!student.name.lowercase().contains(q) && !student.lrn.lowercase().contains(q)) {
                    return@mapNotNull null
                }
            }
            val parent = db.parentDao().primaryForStudent(student.id)?.let { guard.parentForDisplay(it) }
            AdviserLogRecord(
                id = entity.id,
                studentId = entity.studentId,
                studentName = student.name,
                lrn = student.lrn,
                date = LocalDate.parse(entity.date),
                loggedAt = LocalDateTime.parse(entity.loggedAt),
                status = entity.status,
                parentName = parent?.name.orEmpty(),
                parentContact = parent?.contact.orEmpty(),
            )
        }
    }

    suspend fun createAdviserLog(input: AdviserLogInput, actorEmail: String): RecordsResult<AdviserLogRecord> {
        if (input.studentId <= 0L) return RecordsResult.Error("Select a student.")
        if (input.status !in ADVISER_STATUSES) return RecordsResult.Error("Invalid attendance status.")
        val parentErrors = validateParentFields(input.parentName, input.parentContact)
        if (parentErrors.isNotEmpty()) return RecordsResult.Error(parentErrors.joinToString("\n"))
        val student = db.studentDao().getById(input.studentId) ?: return RecordsResult.Error("Student not found.")
        val loggedAt = LocalDateTime.of(input.date, java.time.LocalTime.now())
        return txManager.execute(
            operation = TxOperation.ATTENDANCE_ADVISER_UPSERT,
            entityType = TxEntityType.ATTENDANCE,
            actorEmail = actorEmail,
            summary = "Adviser ${input.status} for ${student.name}",
            entityId = input.studentId,
        ) {
            db.attendanceDao().deleteForStudentOnDate(input.studentId, isoDate.format(input.date), "ADVISER")
            val id = db.attendanceDao().insert(
                AttendanceEntity(
                    studentId = input.studentId,
                    date = isoDate.format(input.date),
                    loggedAt = isoDateTime.format(loggedAt),
                    status = input.status,
                    loggedBy = "ADVISER",
                ),
            )
            updateParent(input.studentId, input.parentName.trim(), input.parentContact.trim())
            audit("CREATE", id, actorEmail, "Adviser ${input.status} for ${student.name}")
            listAdviserLogs(AttendanceLogFilter(date = isoDate.format(input.date))).firstOrNull {
                it.studentId == input.studentId
            } ?: error("Saved but could not reload.")
        }
    }

    suspend fun updateAdviserLog(input: AdviserLogInput, recordId: Long, actorEmail: String): RecordsResult<AdviserLogRecord> {
        val existing = db.attendanceDao().getById(recordId) ?: return RecordsResult.Error("Record not found.")
        if (existing.loggedBy != "ADVISER" && existing.loggedBy != "TEACHER") {
            return RecordsResult.Error("Not an adviser attendance record.")
        }
        if (input.status !in ADVISER_STATUSES) return RecordsResult.Error("Invalid attendance status.")
        val parentErrors = validateParentFields(input.parentName, input.parentContact)
        if (parentErrors.isNotEmpty()) return RecordsResult.Error(parentErrors.joinToString("\n"))
        val student = db.studentDao().getById(existing.studentId) ?: return RecordsResult.Error("Student not found.")
        return txManager.execute(
            operation = TxOperation.ATTENDANCE_ADVISER_UPSERT,
            entityType = TxEntityType.ATTENDANCE,
            actorEmail = actorEmail,
            summary = "Adviser update for ${student.name}",
            entityId = recordId,
        ) {
            db.attendanceDao().update(
                existing.copy(
                    status = input.status,
                    date = isoDate.format(input.date),
                    loggedBy = "ADVISER",
                ),
            )
            updateParent(existing.studentId, input.parentName.trim(), input.parentContact.trim())
            audit("UPDATE", recordId, actorEmail, "Adviser log updated for ${student.name}")
            val parent = db.parentDao().primaryForStudent(student.id)?.let { guard.parentForDisplay(it) }
            AdviserLogRecord(
                recordId,
                existing.studentId,
                student.name,
                student.lrn,
                LocalDate.parse(isoDate.format(input.date)),
                LocalDateTime.parse(existing.loggedAt),
                input.status,
                parent?.name.orEmpty(),
                parent?.contact.orEmpty(),
            )
        }
    }

    suspend fun deleteAdviserLog(recordId: Long, actorEmail: String): RecordsResult<Unit> {
        val existing = db.attendanceDao().getById(recordId) ?: return RecordsResult.Error("Record not found.")
        if (existing.loggedBy != "ADVISER" && existing.loggedBy != "TEACHER") {
            return RecordsResult.Error("Not an adviser attendance record.")
        }
        db.attendanceDao().delete(existing)
        audit("DELETE", recordId, actorEmail, "Adviser log deleted")
        return RecordsResult.Success(Unit)
    }

    suspend fun studentOptions(): List<Student> =
        db.studentDao().getAll().map { s ->
            val p = db.parentDao().primaryForStudent(s.id)?.let { guard.parentForDisplay(it) }
            Student(
                id = s.id.toInt(),
                name = s.name,
                lrn = s.lrn,
                rfidUid = s.rfidUid,
                parentName = p?.name.orEmpty(),
                parentContact = p?.contact.orEmpty(),
                enrollmentId = s.enrollmentId,
            )
        }

    private suspend fun updateParent(studentId: Long, name: String, contact: String) {
        val existing = db.parentDao().primaryForStudent(studentId)
        if (existing == null) {
            db.parentDao().insert(
                guard.parentForStorage(
                    ParentEntity(
                        studentId = studentId,
                        name = name,
                        contact = contact,
                        isPrimary = true,
                    ),
                ),
            )
        } else {
            db.parentDao().updateContactInfo(existing.id, name, guard.encryptField(contact))
        }
    }

    private fun validateParentFields(name: String, contact: String): List<String> {
        val errors = mutableListOf<String>()
        if (name.trim().length < 2) errors.add("Parent name is required.")
        if (contact.isNotBlank() && !contact.matches(Regex("^09[0-9]{9}$"))) {
            errors.add("Contact must be 11 digits starting with 09.")
        }
        return errors
    }

    private suspend fun audit(action: String, entityId: Long, actorEmail: String, summary: String) {
        db.recordAuditLogDao().insert(
            RecordAuditLogEntity(
                action = action,
                entityType = "ATTENDANCE",
                entityId = entityId,
                actorEmail = actorEmail,
                summary = summary,
            ),
        )
        val activityAction = when (action) {
            "CREATE" -> ActivityAction.ATTENDANCE_UPSERT
            "UPDATE" -> ActivityAction.ATTENDANCE_UPSERT
            "DELETE" -> ActivityAction.ATTENDANCE_DELETE
            "UPSERT" -> ActivityAction.ATTENDANCE_UPSERT
            else -> action
        }
        ActivityLogger.get(context).log(
            category = ActivityCategory.RECORDS,
            action = activityAction,
            success = true,
            actorEmail = actorEmail,
            details = summary,
            sessionUuid = SessionStore(context).getTrackingSessionUuid(),
        )
    }

    private fun entityToApp(entity: AttendanceEntity): AttendanceRecord? = runCatching {
        AttendanceRecord(
            studentId = entity.studentId.toInt(),
            date = LocalDate.parse(entity.date),
            loggedAt = LocalDateTime.parse(entity.loggedAt),
            status = entity.status,
            loggedBy = entity.loggedBy,
        )
    }.getOrNull()

    companion object {
        val GATE_STATUSES = setOf("PRESENT", "LATE", "DENIED")
        val ADVISER_STATUSES = setOf("PRESENT", "ABSENT", "LATE", "EXCUSED")

        @Volatile
        private var instance: AttendanceRecordsRepository? = null

        fun get(context: Context): AttendanceRecordsRepository {
            return instance ?: synchronized(this) {
                instance ?: AttendanceRecordsRepository(
                    context.applicationContext,
                    BnhsDatabase.get(context),
                    SensitiveDataGuard.get(context),
                    BusinessTransactionManager.get(context),
                ).also { instance = it }
            }
        }
    }
}
