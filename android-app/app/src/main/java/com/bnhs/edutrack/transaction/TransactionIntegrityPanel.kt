package com.bnhs.edutrack.transaction

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountBalance
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

private val PrimaryDark = Color(0xFF1E1B4B)
private val SuccessMain = Color(0xFF10B981)
private val ErrorMain = Color(0xFFF43F5E)
private val TextSubtitle = Color(0xFF64748B)

@Composable
fun TransactionIntegrityPanel(
    modifier: Modifier = Modifier,
    onStatus: (String) -> Unit = {},
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val txManager = remember { BusinessTransactionManager.get(context) }

    var transactions by remember { mutableStateOf<List<BusinessTransactionView>>(emptyList()) }
    var stats by remember { mutableStateOf(BusinessTransactionManager.TransactionStats(0, 0, 0)) }
    var loading by remember { mutableStateOf(true) }

    fun refresh() {
        scope.launch {
            loading = true
            transactions = txManager.recent(20)
            stats = txManager.stats24h()
            loading = false
        }
    }

    LaunchedEffect(Unit) { refresh() }

    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = Color(0xFFEFF6FF)),
    ) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Row(verticalAlignment = androidx.compose.ui.Alignment.CenterVertically) {
                Icon(Icons.Default.AccountBalance, null, tint = PrimaryDark)
                Spacer(Modifier.width(8.dp))
                Text("ACID business transactions", fontWeight = FontWeight.Bold, color = PrimaryDark)
            }
            Text(
                "CRUD operations run in Room transactions with commit/rollback logging (schema v7).",
                fontSize = 12.sp,
                color = TextSubtitle,
                lineHeight = 16.sp,
            )
            Text(
                "Atomicity · Consistency · Isolation · Durability",
                fontSize = 11.sp,
                fontWeight = FontWeight.SemiBold,
                color = PrimaryDark,
            )

            if (loading) {
                LinearProgressIndicator(Modifier.fillMaxWidth())
            } else {
                Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    StatChip("Committed (24h)", stats.committed, SuccessMain)
                    StatChip("Rolled back", stats.rolledBack, ErrorMain)
                }
            }

            if (transactions.isEmpty()) {
                Text("No transactions yet. Create or edit a student record to see COMMITTED logs.", fontSize = 12.sp, color = TextSubtitle)
            } else {
                Text("Recent transactions", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                transactions.take(8).forEach { tx ->
                    val fmt = SimpleDateFormat("MMM d HH:mm:ss", Locale.getDefault())
                    val color = when (tx.status) {
                        TxStatus.COMMITTED -> SuccessMain
                        TxStatus.ROLLED_BACK -> ErrorMain
                        else -> TextSubtitle
                    }
                    Text(
                        "${fmt.format(Date(tx.startedAt))} · ${tx.operation} · ${tx.status}",
                        fontSize = 10.sp,
                        color = color,
                        fontWeight = FontWeight.Bold,
                    )
                    Text(tx.summary.take(90), fontSize = 10.sp, color = TextSubtitle, maxLines = 2)
                    if (tx.status == TxStatus.ROLLED_BACK && tx.error.isNotBlank()) {
                        Text("↳ ${tx.error.take(80)}", fontSize = 9.sp, color = ErrorMain)
                    }
                }
            }

            TextButton(onClick = {
                refresh()
                onStatus("Transaction log refreshed.")
            }) {
                Text("Refresh transaction log")
            }
        }
    }
}

@Composable
private fun StatChip(label: String, value: Int, color: Color) {
    Surface(shape = RoundedCornerShape(8.dp), color = color.copy(alpha = 0.12f)) {
        Column(Modifier.padding(horizontal = 10.dp, vertical = 6.dp)) {
            Text(label, fontSize = 10.sp, color = TextSubtitle)
            Text(value.toString(), fontWeight = FontWeight.Black, color = color, fontSize = 16.sp)
        }
    }
}
