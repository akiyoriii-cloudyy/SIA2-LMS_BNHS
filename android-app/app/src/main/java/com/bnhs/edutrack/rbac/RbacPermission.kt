package com.bnhs.edutrack.rbac

/**
 * Permission names aligned with Laravel [RbacSeeder] / API middleware.
 */
object RbacPermission {
    const val LMS_PORTAL = "lms.portal"
    const val DASHBOARD_VIEW = "dashboard.view"
    const val RECORDS_MANAGE = "records.manage"
    const val ATTENDANCE_MANAGE = "attendance.manage"
    const val USERS_MANAGE = "users.manage"
    const val ROLES_MANAGE = "roles.manage"
    const val SETTINGS_MANAGE = "settings.manage"
    const val SMS_LOGS_VIEW = "sms_logs.view"
}
