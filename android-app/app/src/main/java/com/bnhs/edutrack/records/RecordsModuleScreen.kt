package com.bnhs.edutrack.records

import com.bnhs.edutrack.ui.*

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.bnhs.edutrack.rbac.RbacAccessDenied
import com.bnhs.edutrack.rbac.RbacEnforcer
import com.bnhs.edutrack.rbac.RbacPermission


@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RecordsModuleScreen(
    viewModel: RecordsViewModel,
    rbac: RbacEnforcer,
    modifier: Modifier = Modifier,
) {
    if (!rbac.canManageStudentRecords()) {
        RbacAccessDenied(RbacPermission.RECORDS_MANAGE, rbac, modifier)
        return
    }
    var recordToDelete by remember { mutableStateOf<StudentRecord?>(null) }
    val canWrite = rbac.canManageStudentRecords()

    Column(modifier = modifier.fillMaxSize()) {
        Text("Student Records", fontWeight = FontWeight.Black, color = PrimaryDark, fontSize = 16.sp)
        Text("CRUD · search · filter · validated SQLite storage", fontSize = 12.sp, color = TextSubtitle)
        Spacer(modifier = Modifier.height(8.dp))

        OutlinedTextField(
            value = viewModel.filter.query,
            onValueChange = { q -> viewModel.updateFilter { it.copy(query = q) } },
            modifier = Modifier.fillMaxWidth(),
            placeholder = { Text("Search name, LRN, RFID, section…") },
            leadingIcon = { Icon(Icons.Default.Search, null) },
            singleLine = true,
        )
        Spacer(modifier = Modifier.height(8.dp))

        Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
            FilterDropdown(
                label = "Grade",
                value = viewModel.filter.gradeLevel,
                options = listOf("") + viewModel.gradeOptions,
                onSelect = { viewModel.updateFilter { f -> f.copy(gradeLevel = it) } },
                modifier = Modifier.weight(1f),
            )
            FilterDropdown(
                label = "Section",
                value = viewModel.filter.section,
                options = listOf("") + viewModel.sectionOptions,
                onSelect = { viewModel.updateFilter { f -> f.copy(section = it) } },
                modifier = Modifier.weight(1f),
            )
        }
        Spacer(modifier = Modifier.height(6.dp))
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            FilterChip(
                selected = viewModel.filter.status.isEmpty(),
                onClick = { viewModel.updateFilter { it.copy(status = "") } },
                label = { Text("All") },
            )
            FilterChip(
                selected = viewModel.filter.status == "ACTIVE",
                onClick = { viewModel.updateFilter { it.copy(status = "ACTIVE") } },
                label = { Text("Active") },
            )
            FilterChip(
                selected = viewModel.filter.status == "ARCHIVED",
                onClick = { viewModel.updateFilter { it.copy(status = "ARCHIVED") } },
                label = { Text("Archived") },
            )
            Spacer(modifier = Modifier.weight(1f))
            IconButton(onClick = { viewModel.refresh() }) {
                Icon(Icons.Default.Refresh, null, tint = PrimaryMain)
            }
        }

        Spacer(modifier = Modifier.height(8.dp))

        if (viewModel.isLoading) {
            LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            Spacer(modifier = Modifier.height(8.dp))
        }

        if (viewModel.records.isEmpty()) {
            Box(Modifier.weight(1f).fillMaxWidth(), contentAlignment = Alignment.Center) {
                Text("No records match your filters.", color = TextSubtitle)
            }
        } else {
            LazyColumn(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(10.dp),
            ) {
                items(viewModel.records, key = { it.id }) { record ->
                    RecordListCard(
                        record = record,
                        onEdit = if (canWrite) { { viewModel.openEdit(record) } } else null,
                        onDelete = if (canWrite) { { recordToDelete = record } } else null,
                    )
                }
            }
        }

        Row(
            modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Text(viewModel.statusMessage, fontSize = 11.sp, color = TextSubtitle, modifier = Modifier.weight(1f))
            if (canWrite) {
                FloatingActionButton(
                    onClick = { viewModel.openCreate() },
                    containerColor = PrimaryMain,
                    contentColor = Color.White,
                ) {
                    Icon(Icons.Default.PersonAdd, null)
                }
            }
        }
    }

    if (viewModel.showEditor) {
        RecordEditorDialog(
            input = viewModel.editingInput,
            isEdit = viewModel.editingInput.id > 0L,
            error = viewModel.formError,
            isSaving = viewModel.isLoading,
            onDismiss = { viewModel.closeEditor() },
            onChange = viewModel::updateEditor,
            onSave = { viewModel.saveEditor() },
        )
    }

    recordToDelete?.let { target ->
        AlertDialog(
            onDismissRequest = { recordToDelete = null },
            icon = { Icon(Icons.Default.DeleteForever, null, tint = ErrorMain) },
            title = { Text("Delete record?") },
            text = { Text("Remove ${target.name} from the device database? This cannot be undone.") },
            confirmButton = {
                TextButton(onClick = {
                    viewModel.deleteRecord(target)
                    recordToDelete = null
                }) { Text("Delete", color = ErrorMain, fontWeight = FontWeight.Bold) }
            },
            dismissButton = {
                TextButton(onClick = { recordToDelete = null }) { Text("Cancel") }
            },
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun FilterDropdown(
    label: String,
    value: String,
    options: List<String>,
    onSelect: (String) -> Unit,
    modifier: Modifier = Modifier,
) {
    var expanded by remember { mutableStateOf(false) }
    ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = it }, modifier = modifier) {
        OutlinedTextField(
            value = value.ifEmpty { "Any" },
            onValueChange = {},
            readOnly = true,
            label = { Text(label) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded) },
            modifier = Modifier.menuAnchor().fillMaxWidth(),
        )
        ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            options.forEach { opt ->
                DropdownMenuItem(
                    text = { Text(if (opt.isEmpty()) "Any" else opt) },
                    onClick = {
                        onSelect(opt)
                        expanded = false
                    },
                )
            }
        }
    }
}

@Composable
private fun RecordListCard(
    record: StudentRecord,
    onEdit: (() -> Unit)?,
    onDelete: (() -> Unit)?,
) {
    Card(shape = RoundedCornerShape(14.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Column(Modifier.padding(14.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text(record.name, fontWeight = FontWeight.Bold, color = PrimaryDark)
                    Text("LRN ${record.lrn} · ${record.rfidUid}", fontSize = 11.sp, color = TextSubtitle)
                    Text("G${record.gradeLevel} · ${record.section}", fontSize = 11.sp, color = TextSubtitle)
                }
                StatusBadge(record.status)
            }
            Spacer(modifier = Modifier.height(6.dp))
            Text("Parent: ${record.parentName} · ${record.parentContact}", fontSize = 11.sp, color = TextSubtitle)
            if (onEdit != null || onDelete != null) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                    if (onEdit != null) {
                        TextButton(onClick = onEdit) {
                            Icon(Icons.Default.Edit, null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("Edit")
                        }
                    }
                    if (onDelete != null) {
                        TextButton(onClick = onDelete) {
                            Icon(Icons.Default.Delete, null, tint = ErrorMain, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("Delete", color = ErrorMain)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun StatusBadge(status: String) {
    val color = if (status == "ACTIVE") SuccessMain else TextSubtitle
    Surface(shape = RoundedCornerShape(8.dp), color = color.copy(alpha = 0.12f)) {
        Text(status, Modifier.padding(horizontal = 8.dp, vertical = 4.dp), fontSize = 10.sp, color = color, fontWeight = FontWeight.Bold)
    }
}

@Composable
private fun RecordEditorDialog(
    input: StudentRecordInput,
    isEdit: Boolean,
    error: String?,
    isSaving: Boolean,
    onDismiss: () -> Unit,
    onChange: (StudentRecordInput) -> Unit,
    onSave: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (isEdit) "Edit record" else "New student record", fontWeight = FontWeight.Bold) },
        text = {
            Column(Modifier.verticalScroll(rememberScrollState()), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                FormField("Full name", input.name) { onChange(input.copy(name = it)) }
                FormField("LRN (10–12 digits)", input.lrn) { onChange(input.copy(lrn = it)) }
                FormField("RFID UID", input.rfidUid) { onChange(input.copy(rfidUid = it)) }
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    FormField("Grade", input.gradeLevel, Modifier.weight(1f)) { onChange(input.copy(gradeLevel = it)) }
                    FormField("Section", input.section, Modifier.weight(1f)) { onChange(input.copy(section = it)) }
                }
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    FilterChip(selected = input.status == "ACTIVE", onClick = { onChange(input.copy(status = "ACTIVE")) }, label = { Text("Active") })
                    FilterChip(selected = input.status == "ARCHIVED", onClick = { onChange(input.copy(status = "ARCHIVED")) }, label = { Text("Archived") })
                }
                FormField("Parent / guardian", input.parentName) { onChange(input.copy(parentName = it)) }
                FormField("Contact (09xxxxxxxxx)", input.parentContact) { onChange(input.copy(parentContact = it)) }
                error?.let {
                    Text(it, color = ErrorMain, fontSize = 12.sp, lineHeight = 16.sp)
                }
            }
        },
        confirmButton = {
            Button(onClick = onSave, enabled = !isSaving) {
                if (isSaving) CircularProgressIndicator(modifier = Modifier.size(18.dp), strokeWidth = 2.dp)
                else Text("Save")
            }
        },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Cancel") } },
    )
}

@Composable
private fun FormField(
    label: String,
    value: String,
    modifier: Modifier = Modifier,
    onValueChange: (String) -> Unit,
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        singleLine = true,
        modifier = modifier.fillMaxWidth(),
    )
}

@Composable
fun rememberRecordsViewModel(
    repository: RecordsRepository,
    actorEmail: String,
    seedOnInit: Boolean = true,
): RecordsViewModel {
    return viewModel(factory = RecordsViewModel.Factory(repository, actorEmail, seedOnInit))
}
