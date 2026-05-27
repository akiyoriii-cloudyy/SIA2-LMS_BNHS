package com.bnhs.edutrack.tracking

import android.content.Context
import com.bnhs.edutrack.auth.AuthSession
import com.bnhs.edutrack.auth.AuthUser
import com.bnhs.edutrack.auth.SessionStore
import com.bnhs.edutrack.data.BnhsDatabase
import com.bnhs.edutrack.security.SensitiveDataGuard
import java.util.UUID

/**
 * Tracks active user sessions on the device (start, heartbeat, end).
 */
class SessionTracker private constructor(
    private val context: Context,
    private val db: BnhsDatabase,
    private val sessionStore: SessionStore,
    private val guard: SensitiveDataGuard,
    private val activityLogger: ActivityLogger,
) {

    suspend fun startSession(user: AuthUser): String {
        val uuid = UUID.randomUUID().toString()
        val now = System.currentTimeMillis()
        db.userSessionDao().insert(
            UserSessionEntity(
                sessionUuid = uuid,
                userId = user.id,
                userEmailEnc = guard.encryptField(user.email),
                userName = user.name,
                roles = user.roles.joinToString(","),
                status = SessionStatus.ACTIVE,
                startedAt = now,
                lastActivityAt = now,
            ),
        )
        sessionStore.saveTrackingSession(uuid)
        activityLogger.log(
            category = ActivityCategory.AUTH,
            action = ActivityAction.LOGIN_SUCCESS,
            success = true,
            actorEmail = user.email,
            details = "Session started on device. Roles: ${user.roles.joinToString()}",
            sessionUuid = uuid,
        )
        return uuid
    }

    suspend fun ensureTracked(session: AuthSession) {
        val existing = sessionStore.getTrackingSessionUuid()
        if (existing != null) {
            val row = db.userSessionDao().findByUuid(existing)
            if (row != null && row.status == SessionStatus.ACTIVE) {
                touchActivity(existing)
                activityLogger.log(
                    category = ActivityCategory.AUTH,
                    action = ActivityAction.SESSION_RESTORE,
                    success = true,
                    actorEmail = session.user.email,
                    details = "Session restored from encrypted storage.",
                    sessionUuid = existing,
                )
                return
            }
        }
        startSession(session.user)
    }

    suspend fun touchActivity(sessionUuid: String? = sessionStore.getTrackingSessionUuid()) {
        val uuid = sessionUuid ?: return
        db.userSessionDao().touch(uuid, System.currentTimeMillis())
    }

    suspend fun endSession(reason: String = "User signed out") {
        val uuid = sessionStore.getTrackingSessionUuid() ?: return
        val now = System.currentTimeMillis()
        db.userSessionDao().end(uuid, now)
        val session = sessionStore.loadSession()
        activityLogger.log(
            category = ActivityCategory.AUTH,
            action = ActivityAction.LOGOUT,
            success = true,
            actorEmail = session?.user?.email,
            details = reason,
            sessionUuid = uuid,
        )
        sessionStore.clearTrackingSession()
    }

    suspend fun activeSessions(): List<UserSessionView> =
        db.userSessionDao().getActive().map { toView(it) }

    suspend fun recentSessions(limit: Int = 10): List<UserSessionView> =
        db.userSessionDao().recent(limit).map { toView(it) }

    private fun toView(row: UserSessionEntity): UserSessionView =
        UserSessionView(
            sessionUuid = row.sessionUuid,
            userName = row.userName,
            userEmail = guard.decryptField(row.userEmailEnc),
            roles = row.roles,
            status = row.status,
            startedAt = row.startedAt,
            lastActivityAt = row.lastActivityAt,
            endedAt = row.endedAt,
        )

    companion object {
        @Volatile
        private var instance: SessionTracker? = null

        fun get(context: Context): SessionTracker =
            instance ?: synchronized(this) {
                instance ?: SessionTracker(
                    context.applicationContext,
                    BnhsDatabase.get(context),
                    SessionStore(context.applicationContext),
                    SensitiveDataGuard.get(context),
                    ActivityLogger.get(context),
                ).also { instance = it }
            }
    }
}
