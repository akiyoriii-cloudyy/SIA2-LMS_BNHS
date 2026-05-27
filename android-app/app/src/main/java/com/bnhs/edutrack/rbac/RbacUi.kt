package com.bnhs.edutrack.rbac

import com.bnhs.edutrack.ui.*

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Shield
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp


@Composable
fun RbacAccessDenied(
    permission: String,
    rbac: RbacEnforcer,
    modifier: Modifier = Modifier,
) {
    Column(
        modifier = modifier.fillMaxSize(),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Icon(Icons.Default.Lock, null, tint = ErrorMain, modifier = Modifier.size(48.dp))
        Spacer(Modifier.height(12.dp))
        Text("Access restricted", fontWeight = FontWeight.Bold, color = PrimaryDark)
        Text(
            rbac.denyReason(permission),
            color = TextSubtitle,
            fontSize = 13.sp,
            modifier = Modifier.padding(horizontal = 24.dp, vertical = 8.dp),
        )
        Text(
            "Roles: ${rbac.roles.joinToString()}",
            fontSize = 11.sp,
            color = TextSubtitle,
        )
    }
}

@Composable
fun RbacHierarchyPanel(rbac: RbacEnforcer, modifier: Modifier = Modifier) {
    Column(
        modifier = modifier.fillMaxWidth(),
        verticalArrangement = Arrangement.spacedBy(10.dp),
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Icon(Icons.Default.Shield, null, tint = PrimaryMain)
            Spacer(Modifier.width(8.dp))
            Text("Role-Based Access Control", fontWeight = FontWeight.Bold, color = PrimaryDark)
        }
        Text(
            "Higher levels inherit more permissions. Endpoints on the server are secured with the same permission names.",
            fontSize = 12.sp,
            color = TextSubtitle,
            lineHeight = 16.sp,
        )
        RoleHierarchy.tiers.forEach { tier ->
            val active = rbac.hasRole(tier.roleKey)
            Card(
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(
                    containerColor = if (active) PrimaryMain.copy(alpha = 0.08f) else Color.White,
                ),
            ) {
                Column(Modifier.padding(12.dp)) {
                    Row(
                        Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Text(tier.label, fontWeight = FontWeight.Bold, color = PrimaryDark, fontSize = 14.sp)
                        Text("Lv ${tier.level}", fontSize = 11.sp, color = TextSubtitle)
                    }
                    Text(tier.description, fontSize = 11.sp, color = TextSubtitle)
                    if (active) {
                        Text("✓ Your role", fontSize = 11.sp, color = SuccessMain, fontWeight = FontWeight.SemiBold)
                    }
                }
            }
        }
        Card(colors = CardDefaults.cardColors(containerColor = Color.White)) {
            Column(Modifier.padding(12.dp)) {
                Text("Your permissions", fontWeight = FontWeight.Bold, color = PrimaryDark)
                Spacer(Modifier.height(6.dp))
                if (rbac.permissions.isEmpty()) {
                    Text("None", color = ErrorMain, fontSize = 12.sp)
                } else {
                    rbac.permissions.sorted().forEach { perm ->
                        PermissionChip(perm, granted = true)
                        Spacer(Modifier.height(4.dp))
                    }
                }
            }
        }
    }
}

@Composable
private fun PermissionChip(name: String, granted: Boolean) {
    Surface(
        shape = RoundedCornerShape(8.dp),
        color = if (granted) SuccessMain.copy(alpha = 0.12f) else ErrorMain.copy(alpha = 0.1f),
    ) {
        Text(
            name,
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
            fontSize = 11.sp,
            color = if (granted) SuccessMain else ErrorMain,
        )
    }
}
