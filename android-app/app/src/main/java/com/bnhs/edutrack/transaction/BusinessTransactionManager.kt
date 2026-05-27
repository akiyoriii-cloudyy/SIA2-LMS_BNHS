package com.bnhs.edutrack.transaction

import android.content.Context
import androidx.room.withTransaction
import com.bnhs.edutrack.data.BnhsDatabase
import com.bnhs.edutrack.records.RecordsResult
import com.bnhs.edutrack.security.SensitiveDataGuard
import com.bnhs.edutrack.tracking.ActivityAction
import com.bnhs.edutrack.tracking.ActivityCategory
import com.bnhs.edutrack.tracking.ActivityLogger
import com.bnhs.edutrack.auth.SessionStore
import java.util.UUID

/**
 * Wraps multi-step CRUD in Room [withTransaction] for atomicity; logs commit/rollback (Member 3 lab).
 *
 * ACID mapping:
 * - **Atomicity** — all steps in one transaction; exception triggers rollback
 * - **Consistency** — validation before execute; FK constraints enforced by SQLite
 * - **Isolation** — Room serializes transactions on the DB connection
 * - **Durability** — SQLite WAL; transaction log row persisted on commit/rollback
 */
class BusinessTransactionManager private constructor(
    private val context: Context,
    private val db: BnhsDatabase,
    private val guard: SensitiveDataGuard,
) {

    /**
     * Runs [block] inside a single database transaction.
     * On success: COMMITTED. On any exception: automatic rollback + ROLLED_BACK log.
     */
    suspend fun <T> execute(
        operation: String,
        entityType: String,
        actorEmail: String,
        summary: String,
        entityId: Long? = null,
        block: suspend () -> T,
    ): RecordsResult<T> {
        val txUuid = UUID.randomUUID().toString()
        val startedAt = System.currentTimeMillis()
        val pending = BusinessTransactionEntity(
            txUuid = txUuid,
            operation = operation,
            entityType = entityType,
            entityId = entityId,
            status = TxStatus.PENDING,
            actorEmailEnc = guard.encryptField(actorEmail),
            summaryEnc = guard.encryptField(summary),
            startedAt = startedAt,
        )
        val logId = db.businessTransactionDao().insert(pending)

        return try {
            val result = db.withTransaction { block() }
            val endedAt = System.currentTimeMillis()
            db.businessTransactionDao().updateCompletion(
                id = logId,
                status = TxStatus.COMMITTED,
                endedAt = endedAt,
                errorEnc = "",
            )
            logActivity(actorEmail, "Transaction committed: $summary", true, txUuid)
            RecordsResult.Success(result)
        } catch (e: Exception) {
            val endedAt = System.currentTimeMillis()
            val errorMsg = e.message ?: e.javaClass.simpleName
            db.businessTransactionDao().updateCompletion(
                id = logId,
                status = TxStatus.ROLLED_BACK,
                endedAt = endedAt,
                errorEnc = guard.encryptField(errorMsg),
            )
            logActivity(actorEmail, "Transaction rolled back: $summary — $errorMsg", false, txUuid)
            RecordsResult.Error("Transaction failed and was rolled back: $errorMsg")
        }
    }

    suspend fun recent(limit: Int = 25): List<BusinessTransactionView> =
        db.businessTransactionDao().recent(limit).map { row ->
            BusinessTransactionView(
                id = row.id,
                txUuid = row.txUuid,
                operation = row.operation,
                entityType = row.entityType,
                entityId = row.entityId,
                status = row.status,
                actorEmail = guard.decryptField(row.actorEmailEnc),
                summary = guard.decryptField(row.summaryEnc),
                error = guard.decryptField(row.errorEnc),
                startedAt = row.startedAt,
                endedAt = row.endedAt,
            )
        }

    suspend fun stats24h(): TransactionStats {
        val since = System.currentTimeMillis() - 24 * 60 * 60 * 1000L
        val rows = db.businessTransactionDao().since(since)
        return TransactionStats(
            committed = rows.count { it.status == TxStatus.COMMITTED },
            rolledBack = rows.count { it.status == TxStatus.ROLLED_BACK },
            pending = rows.count { it.status == TxStatus.PENDING },
        )
    }

    private suspend fun logActivity(actorEmail: String, details: String, success: Boolean, sessionUuid: String?) {
        ActivityLogger.get(context).log(
            category = ActivityCategory.SYSTEM,
            action = if (success) ActivityAction.TX_COMMIT else ActivityAction.TX_ROLLBACK,
            success = success,
            actorEmail = actorEmail,
            details = details,
            sessionUuid = sessionUuid ?: SessionStore(context).getTrackingSessionUuid(),
        )
    }

    data class TransactionStats(
        val committed: Int,
        val rolledBack: Int,
        val pending: Int,
    )

    companion object {
        @Volatile
        private var instance: BusinessTransactionManager? = null

        fun get(context: Context): BusinessTransactionManager =
            instance ?: synchronized(this) {
                instance ?: BusinessTransactionManager(
                    context.applicationContext,
                    BnhsDatabase.get(context),
                    SensitiveDataGuard.get(context),
                ).also { instance = it }
            }
    }
}
