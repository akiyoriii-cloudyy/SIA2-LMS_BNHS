package com.bnhs.edutrack.rbac

/**
 * Role hierarchy (higher level = more authority).
 * Lab mapping: Administrator → Editor (Adviser) → User (Security / Limited).
 */
data class RoleTier(
    val roleKey: String,
    val level: Int,
    val label: String,
    val description: String,
)

object RoleHierarchy {

    val tiers: List<RoleTier> = listOf(
        RoleTier("admin", 300, "Administrator", "Full access — manage users, records, settings"),
        RoleTier("adviser", 200, "Editor (Adviser)", "Modify class content, records, and attendance"),
        RoleTier("security_guard", 150, "User (Security)", "Gate RFID and gate access logs only"),
        RoleTier("user", 100, "User (Limited)", "View dashboard and courses only"),
    )

    /** Static fallback when API permissions are unavailable (offline demo). */
    private val permissionsByRole: Map<String, Set<String>> = mapOf(
        "admin" to setOf(
            RbacPermission.LMS_PORTAL,
            RbacPermission.DASHBOARD_VIEW,
            RbacPermission.RECORDS_MANAGE,
            RbacPermission.ATTENDANCE_MANAGE,
            RbacPermission.USERS_MANAGE,
            RbacPermission.ROLES_MANAGE,
            RbacPermission.SETTINGS_MANAGE,
            RbacPermission.SMS_LOGS_VIEW,
        ),
        "adviser" to setOf(
            RbacPermission.LMS_PORTAL,
            RbacPermission.DASHBOARD_VIEW,
            RbacPermission.RECORDS_MANAGE,
            RbacPermission.ATTENDANCE_MANAGE,
            RbacPermission.SMS_LOGS_VIEW,
        ),
        "security_guard" to setOf(
            RbacPermission.LMS_PORTAL,
            RbacPermission.DASHBOARD_VIEW,
            RbacPermission.ATTENDANCE_MANAGE,
        ),
        "user" to setOf(
            RbacPermission.DASHBOARD_VIEW,
        ),
    )

    fun permissionsForRoles(roles: List<String>): Set<String> {
        val normalized = roles.map { it.trim().lowercase() }
        return normalized.flatMap { permissionsByRole[it].orEmpty() }.toSet()
    }

    fun highestRole(roles: List<String>): RoleTier? {
        val keys = roles.map { it.trim().lowercase() }.toSet()
        return tiers.firstOrNull { it.roleKey in keys }
            ?: tiers.filter { it.roleKey in keys }.maxByOrNull { it.level }
    }
}
