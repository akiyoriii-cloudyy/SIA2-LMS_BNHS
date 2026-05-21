package com.bnhs.edutrack

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import androidx.compose.ui.draw.shadow

private val PrimaryDark = Color(0xFF1E1B4B)
private val SecondaryMain = Color(0xFF06B6D4)

@Composable
fun <T> DashboardBottomNav(
    items: List<Triple<T, ImageVector, String>>,
    selected: T,
    onSelect: (T) -> Unit,
) {
    Box(modifier = Modifier.fillMaxWidth().padding(16.dp), contentAlignment = Alignment.Center) {
        Surface(modifier = Modifier.shadow(16.dp, CircleShape), shape = CircleShape, color = PrimaryDark) {
            Row(modifier = Modifier.padding(horizontal = 8.dp, vertical = 6.dp)) {
                items.forEach { (tab, icon, label) ->
                    val active = tab == selected
                    IconButton(
                        onClick = { if (!active) onSelect(tab) },
                        modifier = Modifier.background(
                            if (active) Color.White.copy(0.1f) else Color.Transparent,
                            CircleShape,
                        ),
                    ) {
                        Icon(
                            icon,
                            contentDescription = label,
                            tint = if (active) SecondaryMain else Color.White.copy(0.5f),
                        )
                    }
                }
            }
        }
    }
}
