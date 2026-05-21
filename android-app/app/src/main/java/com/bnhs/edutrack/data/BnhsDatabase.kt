package com.bnhs.edutrack.data

import android.content.Context
import androidx.room.ColumnInfo
import androidx.room.Dao
import androidx.room.Database
import androidx.room.Delete
import androidx.room.Entity
import androidx.room.ForeignKey
import androidx.room.Index
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.PrimaryKey
import androidx.room.Query
import androidx.room.Room
import androidx.room.RoomDatabase
import androidx.room.Update
import com.bnhs.edutrack.securityaudit.SecurityAuditReportEntity
import com.bnhs.edutrack.securityaudit.SecurityIncidentEntity
import com.bnhs.edutrack.transaction.BusinessTransactionEntity
import com.bnhs.edutrack.tracking.ActivityLogEntity
import com.bnhs.edutrack.tracking.UserSessionEntity

// ============================================================================
//  E N T I T I E S
// ============================================================================
//  The database mirrors the LMS_BNHS web application's tables conceptually so
//  it doubles as a local backup of student / parent / attendance / staff data
//  and a fallback when the device is offline.
// ============================================================================

/** Student/learner master record. */
@Entity(
    tableName = "students",
    indices = [
        Index(value = ["lrn"], unique = true),
        Index(value = ["rfid_uid"], unique = true),
    ]
)
data class StudentEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "name") val name: String,
    @ColumnInfo(name = "lrn") val lrn: String,
    @ColumnInfo(name = "rfid_uid") val rfidUid: String,
    @ColumnInfo(name = "grade_level") val gradeLevel: String = "",
    @ColumnInfo(name = "section") val section: String = "",
    /** ACTIVE or ARCHIVED */
    @ColumnInfo(name = "status") val status: String = "ACTIVE",
    @ColumnInfo(name = "sex") val sex: String = "",
    @ColumnInfo(name = "created_at") val createdAt: Long = System.currentTimeMillis(),
    @ColumnInfo(name = "updated_at") val updatedAt: Long = System.currentTimeMillis(),
)

/** Parent / guardian record. A student can have several but we mark a primary. */
@Entity(
    tableName = "parents",
    foreignKeys = [
        ForeignKey(
            entity = StudentEntity::class,
            parentColumns = ["id"],
            childColumns = ["student_id"],
            onDelete = ForeignKey.CASCADE
        )
    ],
    indices = [Index(value = ["student_id"])]
)
data class ParentEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "student_id") val studentId: Long,
    @ColumnInfo(name = "name") val name: String,
    @ColumnInfo(name = "contact") val contact: String,
    @ColumnInfo(name = "email") val email: String = "",
    @ColumnInfo(name = "relationship") val relationship: String = "Guardian",
    @ColumnInfo(name = "is_primary") val isPrimary: Boolean = true,
    @ColumnInfo(name = "created_at") val createdAt: Long = System.currentTimeMillis(),
    @ColumnInfo(name = "updated_at") val updatedAt: Long = System.currentTimeMillis(),
)

/**
 * Daily attendance entries. We persist only explicit logs (GATE / TEACHER) —
 * the in-memory "SYSTEM" defaults are derived at runtime.
 */
@Entity(
    tableName = "attendance_records",
    foreignKeys = [
        ForeignKey(
            entity = StudentEntity::class,
            parentColumns = ["id"],
            childColumns = ["student_id"],
            onDelete = ForeignKey.CASCADE
        )
    ],
    indices = [
        Index(value = ["student_id", "date"], unique = true),
        Index(value = ["date"])
    ]
)
data class AttendanceEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "student_id") val studentId: Long,
    /** ISO-8601 LocalDate (e.g. 2026-05-11) */
    @ColumnInfo(name = "date") val date: String,
    /** ISO-8601 LocalDateTime (no zone) */
    @ColumnInfo(name = "logged_at") val loggedAt: String,
    @ColumnInfo(name = "status") val status: String,
    @ColumnInfo(name = "logged_by") val loggedBy: String,
    @ColumnInfo(name = "created_at") val createdAt: Long = System.currentTimeMillis(),
)

/**
 * Local staff accounts (offline demo). Passwords stored as salted SHA-256 only.
 */
@Entity(
    tableName = "user_accounts",
    indices = [Index(value = ["username"], unique = true)]
)
data class UserAccountEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "username") val username: String,
    @ColumnInfo(name = "password_hash") val passwordHash: String,
    @ColumnInfo(name = "password_salt") val passwordSalt: String,
    /** "SECURITY_GUARD" or "TEACHER". */
    @ColumnInfo(name = "role") val role: String,
    @ColumnInfo(name = "display_name") val displayName: String,
    @ColumnInfo(name = "assignment") val assignment: String,
    @ColumnInfo(name = "last_login_at") val lastLoginAt: Long? = null,
    @ColumnInfo(name = "created_at") val createdAt: Long = System.currentTimeMillis(),
)

/** Tracks on-device backup files for disaster recovery. */
@Entity(
    tableName = "backup_history",
    indices = [Index(value = ["created_at"])]
)
data class BackupMetaEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "file_name") val fileName: String,
    @ColumnInfo(name = "file_path") val filePath: String,
    @ColumnInfo(name = "record_count") val recordCount: Int,
    @ColumnInfo(name = "checksum_sha256") val checksumSha256: String,
    @ColumnInfo(name = "created_at") val createdAt: Long = System.currentTimeMillis(),
)

/** Audit log for every parent-notification dispatched by the app. */
@Entity(
    tableName = "alert_logs",
    foreignKeys = [
        ForeignKey(
            entity = StudentEntity::class,
            parentColumns = ["id"],
            childColumns = ["student_id"],
            onDelete = ForeignKey.CASCADE
        )
    ],
    indices = [Index(value = ["student_id"])]
)
data class AlertLogEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "student_id") val studentId: Long,
    /** e.g. ABSENCE_WEEK */
    @ColumnInfo(name = "alert_type") val alertType: String,
    @ColumnInfo(name = "message") val message: String,
    @ColumnInfo(name = "recipient_name") val recipientName: String,
    @ColumnInfo(name = "recipient_contact") val recipientContact: String,
    /** SENT / FAILED / NOTIFIED_ONLY */
    @ColumnInfo(name = "status") val status: String,
    @ColumnInfo(name = "error_detail") val errorDetail: String? = null,
    @ColumnInfo(name = "sent_at") val sentAt: Long = System.currentTimeMillis(),
)

/** Security audit trail for records CRUD (who changed what). */
@Entity(
    tableName = "record_audit_logs",
    indices = [Index(value = ["entity_id"]), Index(value = ["created_at"])]
)
data class RecordAuditLogEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "action") val action: String,
    @ColumnInfo(name = "entity_type") val entityType: String = "STUDENT",
    @ColumnInfo(name = "entity_id") val entityId: Long,
    @ColumnInfo(name = "actor_email") val actorEmail: String,
    @ColumnInfo(name = "summary") val summary: String,
    @ColumnInfo(name = "created_at") val createdAt: Long = System.currentTimeMillis(),
)

// ============================================================================
//  D A O s
// ============================================================================

@Dao
interface StudentDao {
    @Query("SELECT * FROM students ORDER BY name ASC")
    suspend fun getAll(): List<StudentEntity>

    @Query("SELECT * FROM students WHERE id = :id LIMIT 1")
    suspend fun getById(id: Long): StudentEntity?

    @Query("SELECT COUNT(*) FROM students")
    suspend fun count(): Int

    @Query(
        "SELECT COUNT(*) FROM students WHERE lrn = :lrn COLLATE NOCASE AND (:excludeId = 0 OR id != :excludeId)",
    )
    suspend fun countByLrn(lrn: String, excludeId: Long): Int

    @Query(
        "SELECT COUNT(*) FROM students WHERE rfid_uid = :rfid COLLATE NOCASE AND (:excludeId = 0 OR id != :excludeId)",
    )
    suspend fun countByRfid(rfid: String, excludeId: Long): Int

    @Query(
        """
        SELECT * FROM students
        WHERE (:query = '' OR name LIKE '%' || :query || '%'
            OR lrn LIKE '%' || :query || '%'
            OR rfid_uid LIKE '%' || :query || '%'
            OR section LIKE '%' || :query || '%')
        AND (:gradeLevel = '' OR grade_level = :gradeLevel)
        AND (:section = '' OR section = :section)
        AND (:status = '' OR status = :status)
        ORDER BY name ASC
        """,
    )
    suspend fun searchFiltered(
        query: String,
        gradeLevel: String,
        section: String,
        status: String,
    ): List<StudentEntity>

    @Query("SELECT DISTINCT grade_level FROM students WHERE grade_level != '' ORDER BY grade_level")
    suspend fun distinctGradeLevels(): List<String>

    @Query("SELECT DISTINCT section FROM students WHERE section != '' ORDER BY section")
    suspend fun distinctSections(): List<String>

    @Insert(onConflict = OnConflictStrategy.ABORT)
    suspend fun insert(student: StudentEntity): Long

    @Update
    suspend fun update(student: StudentEntity)

    @Delete
    suspend fun delete(student: StudentEntity)

    @Query("DELETE FROM students")
    suspend fun deleteAll()
}

@Dao
interface RecordAuditLogDao {
    @Query("SELECT * FROM record_audit_logs ORDER BY created_at DESC")
    suspend fun getAll(): List<RecordAuditLogEntity>

    @Query("SELECT * FROM record_audit_logs ORDER BY created_at DESC LIMIT :limit")
    suspend fun recent(limit: Int = 50): List<RecordAuditLogEntity>

    @Insert
    suspend fun insert(log: RecordAuditLogEntity): Long

    @Query("DELETE FROM record_audit_logs")
    suspend fun deleteAll()
}

@Dao
interface ParentDao {
    @Query("SELECT * FROM parents")
    suspend fun getAll(): List<ParentEntity>

    @Query(
        "SELECT * FROM parents WHERE student_id = :studentId " +
            "ORDER BY is_primary DESC, id ASC LIMIT 1"
    )
    suspend fun primaryForStudent(studentId: Long): ParentEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(parent: ParentEntity): Long

    @Update
    suspend fun update(parent: ParentEntity)

    @Query(
        "UPDATE parents " +
            "SET name = :name, contact = :contact, updated_at = :updatedAt " +
            "WHERE id = :id"
    )
    suspend fun updateContactInfo(
        id: Long,
        name: String,
        contact: String,
        updatedAt: Long = System.currentTimeMillis(),
    )

    @Query("DELETE FROM parents")
    suspend fun deleteAll()
}

@Dao
interface AttendanceDao {
    @Query("SELECT * FROM attendance_records ORDER BY date DESC, logged_at DESC")
    suspend fun getAll(): List<AttendanceEntity>

    @Query("SELECT * FROM attendance_records WHERE id = :id LIMIT 1")
    suspend fun getById(id: Long): AttendanceEntity?

    @Query("SELECT COUNT(*) FROM attendance_records")
    suspend fun count(): Int

    @Query(
        """
        SELECT * FROM attendance_records
        WHERE logged_by = :loggedBy
        AND (:date = '' OR date = :date)
        AND (:status = '' OR status = :status)
        ORDER BY logged_at DESC
        """,
    )
    suspend fun listByRole(
        loggedBy: String,
        date: String,
        status: String,
    ): List<AttendanceEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(record: AttendanceEntity): Long

    @Update
    suspend fun update(record: AttendanceEntity)

    @Delete
    suspend fun delete(record: AttendanceEntity)

    @Query(
        "DELETE FROM attendance_records " +
            "WHERE student_id = :studentId AND date = :date AND logged_by = :loggedBy",
    )
    suspend fun deleteForStudentOnDate(studentId: Long, date: String, loggedBy: String)

    @Query("DELETE FROM attendance_records WHERE logged_by = :loggedBy")
    suspend fun deleteAllForRole(loggedBy: String)

    @Query("DELETE FROM attendance_records")
    suspend fun deleteAll()
}

@Dao
interface UserAccountDao {
    @Query("SELECT * FROM user_accounts ORDER BY role ASC, username ASC")
    suspend fun getAll(): List<UserAccountEntity>

    @Query("SELECT COUNT(*) FROM user_accounts")
    suspend fun count(): Int

    @Query(
        "SELECT * FROM user_accounts " +
            "WHERE LOWER(username) = LOWER(:username) " +
            "AND role = :role LIMIT 1"
    )
    suspend fun findByUsernameAndRole(username: String, role: String): UserAccountEntity?

    @Insert(onConflict = OnConflictStrategy.IGNORE)
    suspend fun insert(account: UserAccountEntity): Long

    @Query("UPDATE user_accounts SET last_login_at = :timestamp WHERE id = :id")
    suspend fun touchLastLogin(id: Long, timestamp: Long)

    @Query("DELETE FROM user_accounts")
    suspend fun deleteAll()
}

@Dao
interface UserSessionDao {
    @Query("SELECT * FROM user_sessions WHERE session_uuid = :uuid LIMIT 1")
    suspend fun findByUuid(uuid: String): UserSessionEntity?

    @Query("SELECT * FROM user_sessions WHERE status = 'ACTIVE' ORDER BY last_activity_at DESC")
    suspend fun getActive(): List<UserSessionEntity>

    @Query("SELECT * FROM user_sessions ORDER BY started_at DESC LIMIT :limit")
    suspend fun recent(limit: Int): List<UserSessionEntity>

    @Insert
    suspend fun insert(session: UserSessionEntity): Long

    @Query("UPDATE user_sessions SET last_activity_at = :at WHERE session_uuid = :uuid")
    suspend fun touch(uuid: String, at: Long)

    @Query("UPDATE user_sessions SET status = 'ENDED', ended_at = :at WHERE session_uuid = :uuid")
    suspend fun end(uuid: String, at: Long)

    @Query("SELECT * FROM user_sessions")
    suspend fun getAll(): List<UserSessionEntity>

    @Query("DELETE FROM user_sessions")
    suspend fun deleteAll()
}

@Dao
interface ActivityLogDao {
    @Query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT :limit")
    suspend fun recent(limit: Int): List<ActivityLogEntity>

    @Query("SELECT * FROM activity_logs WHERE created_at >= :since ORDER BY created_at DESC")
    suspend fun since(since: Long): List<ActivityLogEntity>

    @Insert
    suspend fun insert(log: ActivityLogEntity): Long

    @Query("SELECT * FROM activity_logs")
    suspend fun getAll(): List<ActivityLogEntity>

    @Query("DELETE FROM activity_logs")
    suspend fun deleteAll()
}

@Dao
interface SecurityIncidentDao {
    @Query("SELECT * FROM security_incidents ORDER BY detected_at DESC LIMIT :limit")
    suspend fun recent(limit: Int): List<SecurityIncidentEntity>

    @Query("SELECT * FROM security_incidents WHERE detected_at >= :since ORDER BY detected_at DESC")
    suspend fun since(since: Long): List<SecurityIncidentEntity>

    @Query("SELECT * FROM security_incidents WHERE acknowledged = 0 ORDER BY detected_at DESC")
    suspend fun unacknowledged(): List<SecurityIncidentEntity>

    @Insert
    suspend fun insert(incident: SecurityIncidentEntity): Long

    @Query("UPDATE security_incidents SET acknowledged = 1 WHERE id = :id")
    suspend fun acknowledge(id: Long)

    @Query("SELECT * FROM security_incidents")
    suspend fun getAll(): List<SecurityIncidentEntity>

    @Query("DELETE FROM security_incidents")
    suspend fun deleteAll()
}

@Dao
interface SecurityAuditReportDao {
    @Query("SELECT * FROM security_audit_reports ORDER BY generated_at DESC LIMIT 1")
    suspend fun latest(): SecurityAuditReportEntity?

    @Query("SELECT * FROM security_audit_reports ORDER BY generated_at DESC LIMIT :limit")
    suspend fun recent(limit: Int): List<SecurityAuditReportEntity>

    @Insert
    suspend fun insert(report: SecurityAuditReportEntity): Long

    @Query("SELECT * FROM security_audit_reports")
    suspend fun getAll(): List<SecurityAuditReportEntity>

    @Query("DELETE FROM security_audit_reports")
    suspend fun deleteAll()
}

@Dao
interface BusinessTransactionDao {
    @Query("SELECT * FROM business_transactions ORDER BY started_at DESC LIMIT :limit")
    suspend fun recent(limit: Int): List<BusinessTransactionEntity>

    @Query("SELECT * FROM business_transactions WHERE started_at >= :since")
    suspend fun since(since: Long): List<BusinessTransactionEntity>

    @Insert
    suspend fun insert(tx: BusinessTransactionEntity): Long

    @Query(
        "UPDATE business_transactions SET status = :status, ended_at = :endedAt, error_enc = :errorEnc WHERE id = :id",
    )
    suspend fun updateCompletion(id: Long, status: String, endedAt: Long, errorEnc: String)

    @Query("SELECT * FROM business_transactions")
    suspend fun getAll(): List<BusinessTransactionEntity>

    @Query("DELETE FROM business_transactions")
    suspend fun deleteAll()
}

@Dao
interface BackupMetaDao {
    @Query("SELECT * FROM backup_history ORDER BY created_at DESC")
    suspend fun getAll(): List<BackupMetaEntity>

    @Insert
    suspend fun insert(meta: BackupMetaEntity): Long

    @Query("DELETE FROM backup_history WHERE id = :id")
    suspend fun deleteById(id: Long)
}

@Dao
interface AlertLogDao {
    @Query("SELECT * FROM alert_logs ORDER BY sent_at DESC")
    suspend fun getAll(): List<AlertLogEntity>

    @Insert
    suspend fun insert(log: AlertLogEntity): Long

    @Query("DELETE FROM alert_logs")
    suspend fun deleteAll()
}

// ============================================================================
//  D A T A B A S E
// ============================================================================

@Database(
    entities = [
        StudentEntity::class,
        ParentEntity::class,
        AttendanceEntity::class,
        UserAccountEntity::class,
        AlertLogEntity::class,
        RecordAuditLogEntity::class,
        BackupMetaEntity::class,
        UserSessionEntity::class,
        ActivityLogEntity::class,
        SecurityIncidentEntity::class,
        SecurityAuditReportEntity::class,
        BusinessTransactionEntity::class,
    ],
    version = 7,
    exportSchema = false,
)
abstract class BnhsDatabase : RoomDatabase() {
    abstract fun studentDao(): StudentDao
    abstract fun parentDao(): ParentDao
    abstract fun attendanceDao(): AttendanceDao
    abstract fun userAccountDao(): UserAccountDao
    abstract fun backupMetaDao(): BackupMetaDao
    abstract fun userSessionDao(): UserSessionDao
    abstract fun activityLogDao(): ActivityLogDao
    abstract fun securityIncidentDao(): SecurityIncidentDao
    abstract fun securityAuditReportDao(): SecurityAuditReportDao
    abstract fun businessTransactionDao(): BusinessTransactionDao
    abstract fun alertLogDao(): AlertLogDao
    abstract fun recordAuditLogDao(): RecordAuditLogDao

    companion object {
        private const val DB_NAME = "bnhs_edutrack.db"

        @Volatile
        private var INSTANCE: BnhsDatabase? = null

        fun get(context: Context): BnhsDatabase {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: Room.databaseBuilder(
                    context.applicationContext,
                    BnhsDatabase::class.java,
                    DB_NAME,
                )
                    .fallbackToDestructiveMigration()
                    .build()
                    .also { INSTANCE = it }
            }
        }
    }
}
