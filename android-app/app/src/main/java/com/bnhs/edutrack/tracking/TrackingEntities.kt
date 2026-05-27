package com.bnhs.edutrack.tracking

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey

/** Monitors active and ended user sessions on this device. */
@Entity(
    tableName = "user_sessions",
    indices = [
        Index(value = ["session_uuid"], unique = true),
        Index(value = ["status"]),
        Index(value = ["last_activity_at"]),
    ],
)
data class UserSessionEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "session_uuid") val sessionUuid: String,
    @ColumnInfo(name = "user_id") val userId: Long,
    /** AES-256-GCM encrypted email */
    @ColumnInfo(name = "user_email_enc") val userEmailEnc: String,
    @ColumnInfo(name = "user_name") val userName: String,
    @ColumnInfo(name = "roles") val roles: String,
    @ColumnInfo(name = "status") val status: String = SessionStatus.ACTIVE,
    @ColumnInfo(name = "started_at") val startedAt: Long = System.currentTimeMillis(),
    @ColumnInfo(name = "last_activity_at") val lastActivityAt: Long = System.currentTimeMillis(),
    @ColumnInfo(name = "ended_at") val endedAt: Long? = null,
)

/**
 * Encrypted activity log (login attempts, account/record changes, system events).
 */
@Entity(
    tableName = "activity_logs",
    indices = [
        Index(value = ["session_uuid"]),
        Index(value = ["category"]),
        Index(value = ["created_at"]),
    ],
)
data class ActivityLogEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "session_uuid") val sessionUuid: String? = null,
    @ColumnInfo(name = "category") val category: String,
    @ColumnInfo(name = "action") val action: String,
    @ColumnInfo(name = "success") val success: Boolean,
    @ColumnInfo(name = "actor_email_enc") val actorEmailEnc: String,
    @ColumnInfo(name = "details_enc") val detailsEnc: String,
    @ColumnInfo(name = "created_at") val createdAt: Long = System.currentTimeMillis(),
)

data class ActivityLogView(
    val id: Long,
    val category: String,
    val action: String,
    val success: Boolean,
    val actorEmail: String,
    val details: String,
    val createdAt: Long,
    val sessionUuid: String?,
)

data class UserSessionView(
    val sessionUuid: String,
    val userName: String,
    val userEmail: String,
    val roles: String,
    val status: String,
    val startedAt: Long,
    val lastActivityAt: Long,
    val endedAt: Long?,
)
