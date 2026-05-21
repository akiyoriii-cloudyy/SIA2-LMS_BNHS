package com.bnhs.edutrack.tracking

import android.content.Context
import com.bnhs.edutrack.data.BnhsDatabase
import com.bnhs.edutrack.security.SensitiveDataGuard
import com.bnhs.edutrack.securityaudit.SecurityAuditService

/**
 * Persists user activity with encrypted actor email and details (Member 1 lab).
 */
class ActivityLogger private constructor(
    private val context: Context,
    private val db: BnhsDatabase,
    private val guard: SensitiveDataGuard,
) {

    suspend fun log(
        category: String,
        action: String,
        success: Boolean,
        actorEmail: String?,
        details: String,
        sessionUuid: String? = null,
    ) {
        val email = actorEmail?.trim().orEmpty().ifBlank { "anonymous" }
        db.activityLogDao().insert(
            ActivityLogEntity(
                sessionUuid = sessionUuid,
                category = category,
                action = action,
                success = success,
                actorEmailEnc = guard.encryptField(email),
                detailsEnc = guard.encryptField(details),
            ),
        )
        if (action != ActivityAction.SECURITY_BREACH) {
            SecurityAuditService.get(context).onActivityLogged(
                category = category,
                action = action,
                success = success,
                actorEmail = actorEmail,
                details = details,
            )
        }
    }

    suspend fun recent(limit: Int = 30): List<ActivityLogView> =
        db.activityLogDao().recent(limit).map { row ->
            ActivityLogView(
                id = row.id,
                category = row.category,
                action = row.action,
                success = row.success,
                actorEmail = guard.decryptField(row.actorEmailEnc),
                details = guard.decryptField(row.detailsEnc),
                createdAt = row.createdAt,
                sessionUuid = row.sessionUuid,
            )
        }

    companion object {
        @Volatile
        private var instance: ActivityLogger? = null

        fun get(context: Context): ActivityLogger =
            instance ?: synchronized(this) {
                instance ?: ActivityLogger(
                    context.applicationContext,
                    BnhsDatabase.get(context),
                    SensitiveDataGuard.get(context),
                ).also { instance = it }
            }
    }
}
