package com.bnhs.edutrack.ui

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

@Composable
fun EduTrackStatCard(
    label: String,
    value: String,
    icon: ImageVector,
    iconTint: Color,
    modifier: Modifier = Modifier,
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = LmsColors.White),
        border = androidx.compose.foundation.BorderStroke(1.dp, LmsColors.Border),
        elevation = CardDefaults.cardElevation(0.dp),
    ) {
        Column(Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Surface(
                    shape = RoundedCornerShape(12.dp),
                    color = iconTint.copy(alpha = 0.12f),
                    modifier = Modifier.size(44.dp),
                ) {
                    Box(contentAlignment = Alignment.Center) {
                        androidx.compose.material3.Icon(icon, null, tint = iconTint)
                    }
                }
            }
            Spacer(Modifier.height(10.dp))
            Text(value, fontWeight = FontWeight.Bold, fontSize = 22.sp, color = LmsColors.Navy)
            Text(label, fontSize = 11.sp, color = LmsColors.TextMuted, fontWeight = FontWeight.SemiBold)
        }
    }
}

@Composable
fun EduTrackSectionTitle(text: String, modifier: Modifier = Modifier) {
    Text(
        text,
        modifier = modifier,
        fontWeight = FontWeight.Bold,
        fontSize = 16.sp,
        color = LmsColors.Navy,
    )
}

@Composable
fun EduTrackMutedCaption(text: String, modifier: Modifier = Modifier) {
    Text(text, modifier = modifier, fontSize = 12.sp, color = LmsColors.TextMuted, lineHeight = 16.sp)
}

@Composable
fun EduTrackFab(
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    content: @Composable () -> Unit,
) {
    FloatingActionButton(
        onClick = onClick,
        modifier = modifier,
        containerColor = LmsColors.Navy,
        contentColor = Color.White,
        content = content,
    )
}

@Composable
fun EduTrackFilterChip(
    selected: Boolean,
    onClick: () -> Unit,
    label: String,
) {
    FilterChip(
        selected = selected,
        onClick = onClick,
        label = { Text(label, fontSize = 12.sp) },
        colors = FilterChipDefaults.filterChipColors(
            selectedContainerColor = LmsColors.Gold,
            selectedLabelColor = LmsColors.Navy,
            containerColor = LmsColors.White,
            labelColor = LmsColors.TextMuted,
        ),
        border = FilterChipDefaults.filterChipBorder(
            enabled = true,
            selected = selected,
            borderColor = LmsColors.Border,
            selectedBorderColor = LmsColors.Navy,
        ),
    )
}

@Composable
fun EduTrackQuickStatus(message: String, modifier: Modifier = Modifier) {
    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        color = LmsColors.CreamDark,
        border = androidx.compose.foundation.BorderStroke(1.dp, LmsColors.Border),
    ) {
        Row(Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
            Surface(shape = CircleShape, color = LmsColors.Gold.copy(0.2f), modifier = Modifier.size(28.dp)) {
                Box(contentAlignment = Alignment.Center) {
                    Text("i", color = LmsColors.Navy, fontWeight = FontWeight.Bold, fontSize = 12.sp)
                }
            }
            Spacer(Modifier.width(8.dp))
            Text(message, fontSize = 12.sp, color = LmsColors.TextMuted, fontWeight = FontWeight.Medium)
        }
    }
}
