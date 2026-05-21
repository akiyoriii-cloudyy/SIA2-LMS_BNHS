package com.bnhs.edutrack.backup

import android.content.Context
import com.bnhs.edutrack.auth.SessionStore
import com.bnhs.edutrack.data.BnhsDatabase
import com.bnhs.edutrack.tracking.ActivityAction
import com.bnhs.edutrack.tracking.ActivityCategory
import com.bnhs.edutrack.tracking.ActivityLogger
import com.google.gson.Gson
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.File
import java.security.MessageDigest

/**
 * Restores Room data from a JSON backup produced by [DatabaseBackupManager].
 */
class DatabaseRestoreManager(
    private val context: Context,
    private val db: BnhsDatabase,
) {
    private val gson = Gson()

    suspend fun restoreFromFile(filePath: String, actorEmail: String): Result<Int> = withContext(Dispatchers.IO) {
        runCatching {
            val file = File(filePath)
            require(file.exists()) { "Backup file not found." }
            val json = file.readText()
            val snapshot = gson.fromJson(json, DatabaseSnapshot::class.java)
                ?: error("Invalid backup JSON.")

            DatabaseSnapshot.applyToDb(db, snapshot)

            db.recordAuditLogDao().insert(
                com.bnhs.edutrack.data.RecordAuditLogEntity(
                    entityType = "DATABASE",
                    entityId = 0,
                    action = "RESTORE",
                    actorEmail = actorEmail,
                    summary = "Restored from ${file.name} (${snapshot.totalRecords()} records)",
                ),
            )
            ActivityLogger.get(context).log(
                category = ActivityCategory.SYSTEM,
                action = ActivityAction.DATABASE_RESTORE,
                success = true,
                actorEmail = actorEmail,
                details = "Restored from ${file.name}",
                sessionUuid = SessionStore(context).getTrackingSessionUuid(),
            )
            snapshot.totalRecords()
        }
    }

    suspend fun restoreLatest(actorEmail: String): Result<Int> {
        val latest = db.backupMetaDao().getAll().firstOrNull()
            ?: return Result.failure(IllegalStateException("No backups found. Create a backup first."))
        return restoreFromFile(latest.filePath, actorEmail)
    }

    companion object {
        fun get(context: Context): DatabaseRestoreManager =
            DatabaseRestoreManager(context.applicationContext, BnhsDatabase.get(context))
    }
}
