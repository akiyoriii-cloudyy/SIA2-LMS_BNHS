package com.bnhs.edutrack.transaction

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey

/**
 * ACID business transaction log — durable record of commit/rollback (schema v7).
 */
@Entity(
    tableName = "business_transactions",
    indices = [
        Index(value = ["tx_uuid"], unique = true),
        Index(value = ["status"]),
        Index(value = ["started_at"]),
    ],
)
data class BusinessTransactionEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "tx_uuid") val txUuid: String,
    @ColumnInfo(name = "operation") val operation: String,
    @ColumnInfo(name = "entity_type") val entityType: String,
    @ColumnInfo(name = "entity_id") val entityId: Long? = null,
    @ColumnInfo(name = "status") val status: String = TxStatus.PENDING,
    @ColumnInfo(name = "actor_email_enc") val actorEmailEnc: String,
    @ColumnInfo(name = "summary_enc") val summaryEnc: String,
    @ColumnInfo(name = "error_enc") val errorEnc: String = "",
    @ColumnInfo(name = "started_at") val startedAt: Long = System.currentTimeMillis(),
    @ColumnInfo(name = "ended_at") val endedAt: Long? = null,
)

data class BusinessTransactionView(
    val id: Long,
    val txUuid: String,
    val operation: String,
    val entityType: String,
    val entityId: Long?,
    val status: String,
    val actorEmail: String,
    val summary: String,
    val error: String,
    val startedAt: Long,
    val endedAt: Long?,
)
