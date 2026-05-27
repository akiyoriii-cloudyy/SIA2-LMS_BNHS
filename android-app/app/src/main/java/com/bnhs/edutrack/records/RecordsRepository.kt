package com.bnhs.edutrack.records

import android.content.Context
import com.bnhs.edutrack.Student
import com.bnhs.edutrack.data.BnhsDatabase
import com.bnhs.edutrack.data.BnhsRepository
import com.bnhs.edutrack.data.ParentEntity
import com.bnhs.edutrack.data.RecordAuditLogEntity
import com.bnhs.edutrack.data.StudentEntity
import com.bnhs.edutrack.security.SensitiveDataGuard
import com.bnhs.edutrack.tracking.ActivityAction
import com.bnhs.edutrack.tracking.ActivityCategory
import com.bnhs.edutrack.tracking.ActivityLogger
import com.bnhs.edutrack.auth.SessionStore
import com.bnhs.edutrack.transaction.BusinessTransactionManager
import com.bnhs.edutrack.transaction.TxEntityType
import com.bnhs.edutrack.transaction.TxOperation

class RecordsRepository private constructor(
    private val context: Context,
    private val db: BnhsDatabase,
    private val legacy: BnhsRepository,
    private val guard: SensitiveDataGuard,
    private val txManager: BusinessTransactionManager,
) {

    suspend fun ensureSeedData() = legacy.ensureSeedData()

    suspend fun listFiltered(filter: RecordsFilter): List<StudentRecord> {
        val rows = db.studentDao().searchFiltered(
            query = filter.query.trim(),
            gradeLevel = filter.gradeLevel,
            section = filter.section,
            status = filter.status,
        )
        return rows.map { entityToRecord(it) }
    }

    suspend fun filterOptions(): Pair<List<String>, List<String>> {
        val grades = db.studentDao().distinctGradeLevels()
        val sections = db.studentDao().distinctSections()
        return grades to sections
    }

    suspend fun getById(id: Long): StudentRecord? {
        val student = db.studentDao().getById(id) ?: return null
        return entityToRecord(student)
    }

    suspend fun create(input: StudentRecordInput, actorEmail: String): RecordsResult<StudentRecord> {
        val errors = RecordValidator.validate(input)
        if (errors.isNotEmpty()) return RecordsResult.Error(errors.joinToString("\n"))

        if (db.studentDao().countByLrn(input.lrn.trim(), 0L) > 0) {
            return RecordsResult.Error("LRN already exists.")
        }
        if (db.studentDao().countByRfid(input.rfidUid.trim(), 0L) > 0) {
            return RecordsResult.Error("RFID UID already exists.")
        }

        return txManager.execute(
            operation = TxOperation.STUDENT_CREATE,
            entityType = TxEntityType.STUDENT,
            actorEmail = actorEmail,
            summary = "Create student ${input.name.trim()}",
        ) {
            val now = System.currentTimeMillis()
            val studentId = db.studentDao().insert(
                StudentEntity(
                    name = input.name.trim(),
                    lrn = input.lrn.trim(),
                    rfidUid = input.rfidUid.trim().uppercase(),
                    gradeLevel = input.gradeLevel.trim(),
                    section = input.section.trim(),
                    status = input.status,
                    sex = input.sex.trim(),
                    createdAt = now,
                    updatedAt = now,
                ),
            )
            if (studentId <= 0L) error("Could not create student record.")
            if (input.rfidUid.equals("TX-ROLLBACK", ignoreCase = true)) {
                error("Lab demo: simulated mid-transaction failure")
            }
            db.parentDao().insert(
                guard.parentForStorage(
                    ParentEntity(
                        studentId = studentId,
                        name = input.parentName.trim(),
                        contact = input.parentContact.trim(),
                        isPrimary = true,
                    ),
                ),
            )
            audit("CREATE", studentId, actorEmail, "Created student ${input.name.trim()}")
            entityToRecord(db.studentDao().getById(studentId)!!)
        }
    }

    suspend fun update(input: StudentRecordInput, actorEmail: String): RecordsResult<StudentRecord> {
        if (input.id <= 0L) return RecordsResult.Error("Invalid record id.")
        val existing = db.studentDao().getById(input.id) ?: return RecordsResult.Error("Record not found.")

        val errors = RecordValidator.validate(input)
        if (errors.isNotEmpty()) return RecordsResult.Error(errors.joinToString("\n"))

        if (db.studentDao().countByLrn(input.lrn.trim(), input.id) > 0) {
            return RecordsResult.Error("LRN already used by another student.")
        }
        if (db.studentDao().countByRfid(input.rfidUid.trim(), input.id) > 0) {
            return RecordsResult.Error("RFID UID already used by another student.")
        }

        return txManager.execute(
            operation = TxOperation.STUDENT_UPDATE,
            entityType = TxEntityType.STUDENT,
            actorEmail = actorEmail,
            summary = "Update student ${input.name.trim()}",
            entityId = input.id,
        ) {
            val now = System.currentTimeMillis()
            db.studentDao().update(
                existing.copy(
                    name = input.name.trim(),
                    lrn = input.lrn.trim(),
                    rfidUid = input.rfidUid.trim().uppercase(),
                    gradeLevel = input.gradeLevel.trim(),
                    section = input.section.trim(),
                    status = input.status,
                    sex = input.sex.trim(),
                    updatedAt = now,
                ),
            )
            updateParentInTransaction(input.id, input.parentName.trim(), input.parentContact.trim())
            audit("UPDATE", input.id, actorEmail, "Updated student ${input.name.trim()}")
            entityToRecord(db.studentDao().getById(input.id)!!)
        }
    }

    suspend fun delete(id: Long, actorEmail: String): RecordsResult<Unit> {
        val existing = db.studentDao().getById(id) ?: return RecordsResult.Error("Record not found.")
        return txManager.execute(
            operation = TxOperation.STUDENT_DELETE,
            entityType = TxEntityType.STUDENT,
            actorEmail = actorEmail,
            summary = "Delete student ${existing.name}",
            entityId = id,
        ) {
            db.studentDao().delete(existing)
            audit("DELETE", id, actorEmail, "Deleted student ${existing.name}")
        }
    }

    private suspend fun updateParentInTransaction(studentId: Long, parentName: String, parentContact: String) {
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

    suspend fun recentAudit(limit: Int = 20): List<RecordAuditLogEntity> =
        db.recordAuditLogDao().recent(limit)

    suspend fun loadAppStudents(): List<Student> =
        legacy.loadStudentsWithParent().map { row ->
            Student(
                id = row.id.toInt(),
                name = row.name,
                lrn = row.lrn,
                rfidUid = row.rfidUid,
                parentName = row.parentName,
                parentContact = row.parentContact,
                enrollmentId = row.enrollmentId,
            )
        }

    /** Adviser class roster: server-linked enrollments only, one row per enrollment. */
    suspend fun loadAdviserRosterStudents(): List<Student> =
        db.studentDao().getAll()
            .filter { it.enrollmentId != null || it.serverStudentId != null }
            .distinctBy { it.enrollmentId ?: it.serverStudentId }
            .map { entity ->
                val parent = db.parentDao().primaryForStudent(entity.id)?.let { guard.parentForDisplay(it) }
                Student(
                    id = entity.id.toInt(),
                    name = entity.name,
                    lrn = entity.lrn,
                    rfidUid = entity.rfidUid,
                    parentName = parent?.name.orEmpty(),
                    parentContact = parent?.contact.orEmpty(),
                    enrollmentId = entity.enrollmentId,
                )
            }

    private suspend fun entityToRecord(entity: StudentEntity): StudentRecord {
        val parent = db.parentDao().primaryForStudent(entity.id)?.let { guard.parentForDisplay(it) }
        return StudentRecord(
            id = entity.id,
            name = entity.name,
            lrn = entity.lrn,
            rfidUid = entity.rfidUid,
            gradeLevel = entity.gradeLevel,
            section = entity.section,
            status = entity.status,
            sex = entity.sex,
            parentId = parent?.id ?: 0L,
            parentName = parent?.name.orEmpty(),
            parentContact = parent?.contact.orEmpty(),
        )
    }

    private suspend fun audit(action: String, entityId: Long, actorEmail: String, summary: String) {
        db.recordAuditLogDao().insert(
            RecordAuditLogEntity(
                action = action,
                entityId = entityId,
                actorEmail = actorEmail,
                summary = summary,
            ),
        )
        val activityAction = when (action) {
            "CREATE" -> ActivityAction.STUDENT_CREATE
            "UPDATE" -> ActivityAction.STUDENT_UPDATE
            "DELETE" -> ActivityAction.STUDENT_DELETE
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

    companion object {
        @Volatile
        private var instance: RecordsRepository? = null

        fun get(context: Context): RecordsRepository {
            return instance ?: synchronized(this) {
                val db = BnhsDatabase.get(context)
                instance ?: RecordsRepository(
                    context.applicationContext,
                    db,
                    BnhsRepository.get(context),
                    SensitiveDataGuard.get(context),
                    BusinessTransactionManager.get(context),
                ).also { instance = it }
            }
        }
    }
}
