package com.bnhs.edutrack.backup

import com.bnhs.edutrack.data.AlertLogEntity
import com.bnhs.edutrack.data.AttendanceEntity
import com.bnhs.edutrack.data.BnhsDatabase
import com.bnhs.edutrack.data.ParentEntity
import com.bnhs.edutrack.data.RecordAuditLogEntity
import com.bnhs.edutrack.data.StudentEntity
import com.bnhs.edutrack.data.UserAccountEntity
import com.bnhs.edutrack.securityaudit.SecurityAuditReportEntity
import com.bnhs.edutrack.securityaudit.SecurityIncidentEntity
import com.bnhs.edutrack.transaction.BusinessTransactionEntity
import com.bnhs.edutrack.tracking.ActivityLogEntity
import com.bnhs.edutrack.tracking.UserSessionEntity
import androidx.room.withTransaction

/**
 * Portable JSON snapshot of all Room tables (lab disaster-recovery format).
 */
data class DatabaseSnapshot(
    val schemaVersion: Int = 7,
    val exportedAt: Long = System.currentTimeMillis(),
    val students: List<StudentEntity> = emptyList(),
    val parents: List<ParentEntity> = emptyList(),
    val attendance: List<AttendanceEntity> = emptyList(),
    val userAccounts: List<UserAccountEntity> = emptyList(),
    val alertLogs: List<AlertLogEntity> = emptyList(),
    val auditLogs: List<RecordAuditLogEntity> = emptyList(),
    val userSessions: List<UserSessionEntity> = emptyList(),
    val activityLogs: List<ActivityLogEntity> = emptyList(),
    val securityIncidents: List<SecurityIncidentEntity> = emptyList(),
    val securityReports: List<SecurityAuditReportEntity> = emptyList(),
    val businessTransactions: List<BusinessTransactionEntity> = emptyList(),
) {
    fun totalRecords(): Int =
        students.size + parents.size + attendance.size + userAccounts.size + alertLogs.size +
            auditLogs.size + userSessions.size + activityLogs.size +
            securityIncidents.size + securityReports.size + businessTransactions.size

    companion object {
        suspend fun from(db: BnhsDatabase): DatabaseSnapshot = DatabaseSnapshot(
            students = db.studentDao().getAll(),
            parents = db.parentDao().getAll(),
            attendance = db.attendanceDao().getAll(),
            userAccounts = db.userAccountDao().getAll(),
            alertLogs = db.alertLogDao().getAll(),
            auditLogs = db.recordAuditLogDao().getAll(),
            userSessions = db.userSessionDao().getAll(),
            activityLogs = db.activityLogDao().getAll(),
            securityIncidents = db.securityIncidentDao().getAll(),
            securityReports = db.securityAuditReportDao().getAll(),
            businessTransactions = db.businessTransactionDao().getAll(),
        )

        suspend fun applyToDb(db: BnhsDatabase, snapshot: DatabaseSnapshot) {
            db.withTransaction {
                // Delete child tables first (SQLite foreign-key order).
                db.alertLogDao().deleteAll()
                db.attendanceDao().deleteAll()
                db.parentDao().deleteAll()
                db.studentDao().deleteAll()
                db.userAccountDao().deleteAll()
                db.recordAuditLogDao().deleteAll()
                db.activityLogDao().deleteAll()
                db.userSessionDao().deleteAll()
                db.securityIncidentDao().deleteAll()
                db.securityAuditReportDao().deleteAll()
                db.businessTransactionDao().deleteAll()

                // Students get new auto-increment ids — remap FKs for dependents.
                val studentIdMap = mutableMapOf<Long, Long>()
                snapshot.students.forEach { student ->
                    val oldId = student.id
                    val newId = db.studentDao().insert(student.copy(id = 0))
                    if (oldId > 0L) {
                        studentIdMap[oldId] = newId
                    }
                }

                fun remapStudentId(oldStudentId: Long): Long =
                    studentIdMap[oldStudentId]
                        ?: error("Backup references missing student id=$oldStudentId")

                snapshot.parents.forEach { parent ->
                    db.parentDao().insert(
                        parent.copy(
                            id = 0,
                            studentId = remapStudentId(parent.studentId),
                        ),
                    )
                }
                snapshot.attendance.forEach { row ->
                    db.attendanceDao().insert(
                        row.copy(
                            id = 0,
                            studentId = remapStudentId(row.studentId),
                        ),
                    )
                }
                snapshot.alertLogs.forEach { row ->
                    db.alertLogDao().insert(
                        row.copy(
                            id = 0,
                            studentId = remapStudentId(row.studentId),
                        ),
                    )
                }
                snapshot.userAccounts.forEach { db.userAccountDao().insert(it.copy(id = 0)) }
                snapshot.auditLogs.forEach { db.recordAuditLogDao().insert(it.copy(id = 0)) }
                snapshot.userSessions.forEach { db.userSessionDao().insert(it.copy(id = 0)) }
                snapshot.activityLogs.forEach { db.activityLogDao().insert(it.copy(id = 0)) }
                snapshot.securityIncidents.forEach { db.securityIncidentDao().insert(it.copy(id = 0)) }
                snapshot.securityReports.forEach { db.securityAuditReportDao().insert(it.copy(id = 0)) }
                snapshot.businessTransactions.forEach { db.businessTransactionDao().insert(it.copy(id = 0)) }
            }
        }
    }
}
