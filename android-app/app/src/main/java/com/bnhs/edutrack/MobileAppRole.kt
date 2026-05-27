package com.bnhs.edutrack

import com.bnhs.edutrack.auth.AuthSession
import com.bnhs.edutrack.rbac.RbacEnforcer
import com.bnhs.edutrack.rbac.RbacPermission

enum class MobileAppRole(
    val title: String,
    val subtitle: String,
) {
    ADMIN("Admin Control", "School operations & monitoring"),
    SECURITY("Security Gate", "RFID entrance terminal"),
    ADVISER("Adviser Portal", "Class attendance & parent alerts"),
    UNSUPPORTED("Access not configured", "Contact the school administrator"),
}

fun resolveMobileAppRole(roles: List<String>, permissions: Set<String> = emptySet()): MobileAppRole {
    if (permissions.isNotEmpty() && RbacPermission.LMS_PORTAL !in permissions) {
        return MobileAppRole.UNSUPPORTED
    }
    val normalized = roles.map { it.trim().lowercase() }.toSet()
    return when {
        "admin" in normalized -> MobileAppRole.ADMIN
        "security_guard" in normalized || "security" in normalized -> MobileAppRole.SECURITY
        "adviser" in normalized -> MobileAppRole.ADVISER
        permissions.contains(RbacPermission.RECORDS_MANAGE) &&
            permissions.contains(RbacPermission.ATTENDANCE_MANAGE) -> MobileAppRole.ADVISER
        permissions.contains(RbacPermission.ATTENDANCE_MANAGE) -> MobileAppRole.SECURITY
        else -> MobileAppRole.UNSUPPORTED
    }
}

fun AuthSession.mobileAppRole(): MobileAppRole = RbacEnforcer.from(this).mobileAppRole()
