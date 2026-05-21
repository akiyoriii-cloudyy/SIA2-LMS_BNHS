package com.bnhs.edutrack.tracking

object ActivityCategory {
    const val AUTH = "AUTH"
    const val ACCOUNT = "ACCOUNT"
    const val RECORDS = "RECORDS"
    const val SYSTEM = "SYSTEM"
}

object ActivityAction {
    const val LOGIN_SUCCESS = "LOGIN_SUCCESS"
    const val LOGIN_FAILURE = "LOGIN_FAILURE"
    const val LOGOUT = "LOGOUT"
    const val SESSION_RESTORE = "SESSION_RESTORE"
    const val SESSION_HEARTBEAT = "SESSION_HEARTBEAT"
    const val PASSWORD_RESET_REQUEST = "PASSWORD_RESET_REQUEST"
    const val STUDENT_CREATE = "STUDENT_CREATE"
    const val STUDENT_UPDATE = "STUDENT_UPDATE"
    const val STUDENT_DELETE = "STUDENT_DELETE"
    const val GUARDIAN_UPDATE = "GUARDIAN_UPDATE"
    const val ATTENDANCE_UPSERT = "ATTENDANCE_UPSERT"
    const val ATTENDANCE_DELETE = "ATTENDANCE_DELETE"
    const val DATABASE_BACKUP = "DATABASE_BACKUP"
    const val DATABASE_RESTORE = "DATABASE_RESTORE"
    const val SECURITY_BREACH = "SECURITY_BREACH"
    const val TX_COMMIT = "TX_COMMIT"
    const val TX_ROLLBACK = "TX_ROLLBACK"
}

object SessionStatus {
    const val ACTIVE = "ACTIVE"
    const val ENDED = "ENDED"
}
