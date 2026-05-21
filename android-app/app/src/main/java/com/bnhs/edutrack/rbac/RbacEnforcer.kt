package com.bnhs.edutrack.rbac

import com.bnhs.edutrack.MobileAppRole
import com.bnhs.edutrack.auth.AuthSession
import com.bnhs.edutrack.resolveMobileAppRole

class RbacEnforcer(
    val roles: List<String>,
    val permissions: Set<String>,
) {
    fun hasPermission(permission: String): Boolean = permission in permissions

    fun hasAnyPermission(vararg permissions: String): Boolean =
        permissions.any { it in this.permissions }

    fun hasRole(roleKey: String): Boolean =
        roles.any { it.equals(roleKey, ignoreCase = true) }

    fun canAccessPortal(): Boolean = hasPermission(RbacPermission.LMS_PORTAL)

    fun canViewDashboard(): Boolean = hasPermission(RbacPermission.DASHBOARD_VIEW)

    /** Admin student master records (Records tab). */
    fun canManageStudentRecords(): Boolean = hasPermission(RbacPermission.RECORDS_MANAGE)

    /** Gate logs, RFID scan, adviser attendance. */
    fun canManageAttendance(): Boolean = hasPermission(RbacPermission.ATTENDANCE_MANAGE)

    fun canManageUsers(): Boolean = hasPermission(RbacPermission.USERS_MANAGE)

    fun canManageRoles(): Boolean = hasPermission(RbacPermission.ROLES_MANAGE)

    fun mobileAppRole(): MobileAppRole = resolveMobileAppRole(roles, permissions)

    fun denyReason(permission: String): String =
        "Access denied. Your role does not have permission: $permission"

    companion object {
        fun from(session: AuthSession): RbacEnforcer =
            from(session.user.roles, session.user.permissions)

        fun from(roles: List<String>, serverPermissions: List<String>): RbacEnforcer {
            val merged = if (serverPermissions.isNotEmpty()) {
                serverPermissions.toSet()
            } else {
                RoleHierarchy.permissionsForRoles(roles)
            }
            return RbacEnforcer(roles, merged)
        }
    }
}
