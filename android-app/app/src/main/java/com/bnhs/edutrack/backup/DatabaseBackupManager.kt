package com.bnhs.edutrack.backup

import android.content.Context
import com.bnhs.edutrack.auth.SessionStore
import com.bnhs.edutrack.data.BackupMetaEntity
import com.bnhs.edutrack.data.BnhsDatabase
import com.bnhs.edutrack.tracking.ActivityAction
import com.bnhs.edutrack.tracking.ActivityCategory
import com.bnhs.edutrack.tracking.ActivityLogger
import com.google.gson.Gson
import com.google.gson.GsonBuilder
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.File
import java.security.MessageDigest
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

/**
 * Exports the full Room database to a versioned JSON backup file.
 */
class DatabaseBackupManager(
    private val context: Context,
    private val db: BnhsDatabase,
) {
    private val gson: Gson = GsonBuilder().setPrettyPrinting().create()
    private val backupDir: File
        get() = File(context.getExternalFilesDir(null), "backups").apply { mkdirs() }

    suspend fun createBackup(actorEmail: String): Result<BackupMetaEntity> = withContext(Dispatchers.IO) {
        runCatching {
            val snapshot = DatabaseSnapshot.from(db)
            val json = gson.toJson(snapshot)
            val checksum = sha256(json)
            val stamp = SimpleDateFormat("yyyyMMdd_HHmmss", Locale.US).format(Date())
            val fileName = "bnhs_backup_v${snapshot.schemaVersion}_$stamp.json"
            val file = File(backupDir, fileName)
            file.writeText(json)

            val meta = BackupMetaEntity(
                fileName = fileName,
                filePath = file.absolutePath,
                recordCount = snapshot.totalRecords(),
                checksumSha256 = checksum,
            )
            val id = db.backupMetaDao().insert(meta)
            db.recordAuditLogDao().insert(
                com.bnhs.edutrack.data.RecordAuditLogEntity(
                    entityType = "DATABASE",
                    entityId = id,
                    action = "BACKUP",
                    actorEmail = actorEmail,
                    summary = "Backup $fileName (${meta.recordCount} records)",
                ),
            )
            ActivityLogger.get(context).log(
                category = ActivityCategory.SYSTEM,
                action = ActivityAction.DATABASE_BACKUP,
                success = true,
                actorEmail = actorEmail,
                details = "Backup $fileName (${meta.recordCount} records)",
                sessionUuid = SessionStore(context).getTrackingSessionUuid(),
            )
            meta.copy(id = id)
        }
    }

    suspend fun listBackups(): List<BackupMetaEntity> = withContext(Dispatchers.IO) {
        db.backupMetaDao().getAll()
    }

    fun backupDirectory(): File = backupDir

    private fun sha256(text: String): String {
        val digest = MessageDigest.getInstance("SHA-256")
        val bytes = digest.digest(text.toByteArray(Charsets.UTF_8))
        return bytes.joinToString("") { "%02x".format(it) }
    }

    companion object {
        fun get(context: Context): DatabaseBackupManager =
            DatabaseBackupManager(context.applicationContext, BnhsDatabase.get(context))
    }
}
