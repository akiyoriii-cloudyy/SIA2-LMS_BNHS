package com.bnhs.edutrack.ui

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.bnhs.edutrack.MobileAppRole

/** Matches web `lms.css` instructional theme (admin / adviser). */
object LmsColors {
    val Navy = Color(0xFF0F5A2A)
    val NavyLight = Color(0xFF137036)
    val NavyMid = Color(0xFF188341)
    val Gold = Color(0xFF2FB344)
    val GoldLight = Color(0xFF8CE99A)
    val Cream = Color(0xFFF7FAF7)
    val CreamDark = Color(0xFFEDF3EE)
    val Sage = Color(0xFF1F7A34)
    val SageLight = Color(0xFF74C69D)
    val Text = Color(0xFF0F2416)
    val TextMuted = Color(0xFF416A4D)
    val Border = Color(0xFFD7E5DB)
    val White = Color.White
    val Red = Color(0xFFC94A3A)
    val Passed = Color(0xFF2D7A4F)

}

/**
 * App-wide colors aligned with web `lms.css` (admin / adviser green theme).
 * Use these instead of legacy indigo/cyan constants.
 */
object EduTrackColors {
    val PrimaryDark = LmsColors.Navy
    val PrimaryMain = LmsColors.Gold
    val SecondaryMain = LmsColors.SageLight
    val AccentMain = LmsColors.GoldLight
    val SuccessMain = LmsColors.Passed
    val ErrorMain = LmsColors.Red
    val BgStart = LmsColors.Cream
    val BgEnd = LmsColors.CreamDark
    val TextSubtitle = LmsColors.TextMuted
    val CardSurface = LmsColors.White
    val Border = LmsColors.Border
}

/** Top-level aliases — Kotlin cannot `import EduTrackColors.*` from an object. */
val PrimaryDark = EduTrackColors.PrimaryDark
val PrimaryMain = EduTrackColors.PrimaryMain
val SecondaryMain = EduTrackColors.SecondaryMain
val AccentMain = EduTrackColors.AccentMain
val SuccessMain = EduTrackColors.SuccessMain
val ErrorMain = EduTrackColors.ErrorMain
val BgStart = EduTrackColors.BgStart
val BgEnd = EduTrackColors.BgEnd
val TextSubtitle = EduTrackColors.TextSubtitle

fun lmsColorScheme(@Suppress("UNUSED_PARAMETER") role: MobileAppRole): ColorScheme = lightColorScheme(
    primary = LmsColors.Navy,
    onPrimary = Color.White,
    primaryContainer = LmsColors.GoldLight.copy(alpha = 0.35f),
    onPrimaryContainer = LmsColors.Navy,
    secondary = LmsColors.Gold,
    onSecondary = LmsColors.Navy,
    secondaryContainer = LmsColors.SageLight.copy(alpha = 0.25f),
    onSecondaryContainer = LmsColors.Navy,
    tertiary = LmsColors.Sage,
    onTertiary = Color.White,
    background = LmsColors.Cream,
    onBackground = LmsColors.Text,
    surface = LmsColors.White,
    onSurface = LmsColors.Text,
    surfaceVariant = LmsColors.CreamDark,
    onSurfaceVariant = LmsColors.TextMuted,
    outline = LmsColors.Border,
    outlineVariant = LmsColors.Border,
)

@Composable
fun LmsMeshBackground(modifier: Modifier = Modifier) {
    Box(
        modifier = modifier
            .fillMaxSize()
            .background(LmsColors.Cream),
    ) {
        Canvas(modifier = Modifier.fillMaxSize()) {
            drawCircle(
                brush = Brush.radialGradient(
                    colors = listOf(LmsColors.SageLight.copy(alpha = 0.12f), Color.Transparent),
                    center = Offset(size.width * 0.2f, size.height * 0.15f),
                    radius = size.minDimension * 0.55f,
                ),
                radius = size.minDimension * 0.55f,
                center = Offset(size.width * 0.2f, size.height * 0.15f),
            )
            drawCircle(
                brush = Brush.radialGradient(
                    colors = listOf(LmsColors.GoldLight.copy(alpha = 0.15f), Color.Transparent),
                    center = Offset(size.width * 0.85f, size.height * 0.75f),
                    radius = size.minDimension * 0.5f,
                ),
                radius = size.minDimension * 0.5f,
                center = Offset(size.width * 0.85f, size.height * 0.75f),
            )
        }
    }
}

@Composable
fun LmsHeaderBar(
    roleLabel: String,
    userName: String,
    onMenuClick: () -> Unit,
    modifier: Modifier = Modifier,
    trailing: @Composable RowScope.() -> Unit = {},
) {
    Surface(
        modifier = modifier.fillMaxWidth(),
        color = LmsColors.Navy,
        shadowElevation = 6.dp,
    ) {
        Row(
            Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Surface(
                shape = RoundedCornerShape(10.dp),
                color = LmsColors.Gold,
                modifier = Modifier.size(40.dp),
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Text("ET", color = LmsColors.Navy, fontWeight = FontWeight.Black, fontSize = 14.sp)
                }
            }
            Spacer(Modifier.width(12.dp))
            Column(Modifier.weight(1f)) {
                Text(
                    "EduTrack",
                    color = Color.White,
                    fontWeight = FontWeight.Bold,
                    fontSize = 17.sp,
                )
                Text(
                    "SHS MANAGEMENT SYSTEM",
                    color = Color.White.copy(alpha = 0.75f),
                    fontSize = 9.sp,
                    letterSpacing = 0.5.sp,
                )
                Text(
                    "$roleLabel · $userName",
                    color = LmsColors.GoldLight,
                    fontSize = 11.sp,
                    modifier = Modifier.padding(top = 2.dp),
                )
            }
            trailing()
            IconButton(onClick = onMenuClick) {
                Icon(Icons.Filled.MoreVert, contentDescription = "Menu", tint = Color.White)
            }
        }
    }
}

@Composable
fun LmsDashTopBar(
    breadcrumb: String,
    modifier: Modifier = Modifier,
    actions: @Composable RowScope.() -> Unit = {},
) {
    Row(
        modifier = modifier.fillMaxWidth().padding(bottom = 12.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Row(Modifier.weight(1f), verticalAlignment = Alignment.CenterVertically) {
            Text("EduTrack", fontWeight = FontWeight.Bold, fontSize = 18.sp, color = LmsColors.Navy)
            Text(" / ", color = LmsColors.Border, modifier = Modifier.padding(horizontal = 4.dp))
            Text(breadcrumb, fontSize = 13.sp, color = LmsColors.TextMuted)
        }
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp), content = actions)
    }
}

@Composable
fun LmsDashPanel(
    title: String,
    subtitle: String,
    modifier: Modifier = Modifier,
    content: @Composable ColumnScope.() -> Unit,
) {
    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = LmsColors.White),
        border = androidx.compose.foundation.BorderStroke(1.dp, LmsColors.Border),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
    ) {
        Column {
            Column(
                Modifier
                    .fillMaxWidth()
                    .border(1.dp, LmsColors.Border, RoundedCornerShape(topStart = 16.dp, topEnd = 16.dp))
                    .padding(horizontal = 20.dp, vertical = 14.dp),
            ) {
                Text(title, fontWeight = FontWeight.Bold, fontSize = 13.sp, color = LmsColors.Navy)
                Text(subtitle, fontSize = 11.sp, color = LmsColors.TextMuted, modifier = Modifier.padding(top = 2.dp))
            }
            HorizontalDivider(color = LmsColors.Border)
            Column(Modifier.padding(horizontal = 20.dp, vertical = 18.dp), content = content)
        }
    }
}

@Composable
fun LmsOutlinedField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    modifier: Modifier = Modifier,
    readOnly: Boolean = false,
    keyboardOptions: KeyboardOptions = KeyboardOptions.Default,
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        modifier = modifier.fillMaxWidth(),
        label = { Text(label, fontSize = 12.sp) },
        readOnly = readOnly,
        enabled = !readOnly,
        singleLine = true,
        keyboardOptions = keyboardOptions,
        shape = RoundedCornerShape(10.dp),
        colors = OutlinedTextFieldDefaults.colors(
            focusedBorderColor = LmsColors.Navy,
            unfocusedBorderColor = LmsColors.Border,
            focusedLabelColor = LmsColors.Navy,
            unfocusedLabelColor = LmsColors.TextMuted,
        ),
    )
}

@Composable
fun LmsPrimaryButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
) {
    Button(
        onClick = onClick,
        modifier = modifier.fillMaxWidth().height(48.dp),
        enabled = enabled,
        shape = RoundedCornerShape(10.dp),
        colors = ButtonDefaults.buttonColors(
            containerColor = LmsColors.Navy,
            contentColor = Color.White,
            disabledContainerColor = LmsColors.Border,
        ),
    ) {
        Text(text, fontWeight = FontWeight.Bold)
    }
}

@Composable
fun RoleBadge(label: String) {
    Surface(
        shape = RoundedCornerShape(999.dp),
        color = LmsColors.GoldLight.copy(alpha = 0.35f),
        border = androidx.compose.foundation.BorderStroke(1.dp, LmsColors.Border),
    ) {
        Text(
            label,
            Modifier.padding(horizontal = 10.dp, vertical = 4.dp),
            fontSize = 11.sp,
            fontWeight = FontWeight.Bold,
            color = LmsColors.Navy,
        )
    }
}
