package com.bnhs.edutrack.rbac

import com.bnhs.edutrack.MobileAppRole
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class RbacEnforcerTest {

    @Test
    fun adminRole_hasFullPermissions() {
        val rbac = RbacEnforcer.from(listOf("admin"), emptyList())
        assertTrue(rbac.canManageUsers())
        assertTrue(rbac.canManageStudentRecords())
        assertTrue(rbac.canManageAttendance())
        assertEquals(MobileAppRole.ADMIN, rbac.mobileAppRole())
    }

    @Test
    fun securityRole_canManageAttendance_only() {
        val rbac = RbacEnforcer.from(listOf("security_guard"), emptyList())
        assertFalse(rbac.canManageStudentRecords())
        assertTrue(rbac.canManageAttendance())
        assertEquals(MobileAppRole.SECURITY, rbac.mobileAppRole())
    }

    @Test
    fun serverPermissions_overrideRoleFallback() {
        val rbac = RbacEnforcer.from(
            listOf("admin"),
            listOf(RbacPermission.LMS_PORTAL, RbacPermission.DASHBOARD_VIEW),
        )
        assertFalse(rbac.canManageStudentRecords())
        assertTrue(rbac.canViewDashboard())
    }

    @Test
    fun missingPortalPermission_yieldsUnsupportedRole() {
        val rbac = RbacEnforcer.from(
            listOf("admin"),
            listOf(RbacPermission.RECORDS_MANAGE),
        )
        assertEquals(MobileAppRole.UNSUPPORTED, rbac.mobileAppRole())
    }
}
