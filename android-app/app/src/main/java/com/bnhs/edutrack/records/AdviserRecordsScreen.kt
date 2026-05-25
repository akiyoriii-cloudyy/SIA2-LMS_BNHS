package com.bnhs.edutrack.records

import com.bnhs.edutrack.ui.*

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.bnhs.edutrack.Student
import com.bnhs.edutrack.rbac.RbacAccessDenied
import com.bnhs.edutrack.rbac.RbacEnforcer
import com.bnhs.edutrack.rbac.RbacPermission
import kotlinx.coroutines.launch
import java.time.LocalDate
import java.time.format.DateTimeFormatter


class AdviserRecordsViewModel(
    private val repository: AttendanceRecordsRepository,
    private val actorEmail: String,
) : ViewModel() {
    var logs by mutableStateOf<List<AdviserLogRecord>>(emptyList())
    var students by mutableStateOf<List<Student>>(emptyList())
    var filter by mutableStateOf(AttendanceLogFilter())
    var filterDate by mutableStateOf(LocalDate.now())
    var isLoading by mutableStateOf(false)
    var statusMessage by mutableStateOf("")
    var formError by mutableStateOf<String?>(null)
    var showEditor by mutableStateOf(false)
    var editing by mutableStateOf(AdviserLogInput())
    var editingId by mutableStateOf(0L)
    var onDataChanged: (() -> Unit)? = null

    init {
        viewModelScope.launch {
            students = repository.studentOptions()
            refresh()
        }
    }

    fun refresh() {
        viewModelScope.launch {
            isLoading = true
            filter = filter.copy(date = DateTimeFormatter.ISO_LOCAL_DATE.format(filterDate))
            logs = repository.listAdviserLogs(filter)
            isLoading = false
            statusMessage = "${logs.size} attendance record(s)."
            onDataChanged?.invoke()
        }
    }

    fun openCreate() {
        editingId = 0L
        editing = AdviserLogInput(date = filterDate)
        formError = null
        showEditor = true
    }

    fun openEdit(record: AdviserLogRecord) {
        editingId = record.id
        editing = AdviserLogInput(
            studentId = record.studentId,
            date = record.date,
            status = record.status,
            parentName = record.parentName,
            parentContact = record.parentContact,
        )
        formError = null
        showEditor = true
    }

    fun save() {
        viewModelScope.launch {
            isLoading = true
            val result = if (editingId > 0L) {
                repository.updateAdviserLog(editing, editingId, actorEmail)
            } else {
                repository.createAdviserLog(editing, actorEmail)
            }
            isLoading = false
            when (result) {
                is RecordsResult.Success -> {
                    showEditor = false
                    formError = null
                    statusMessage = if (editingId > 0L) "Attendance updated." else "Attendance created."
                    refresh()
                }
                is RecordsResult.Error -> formError = result.message
            }
        }
    }

    fun delete(record: AdviserLogRecord) {
        viewModelScope.launch {
            when (val result = repository.deleteAdviserLog(record.id, actorEmail)) {
                is RecordsResult.Success -> {
                    statusMessage = "Attendance record deleted."
                    refresh()
                }
                is RecordsResult.Error -> statusMessage = result.message
            }
        }
    }

    class Factory(private val repo: AttendanceRecordsRepository, private val email: String) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(c: Class<T>): T = AdviserRecordsViewModel(repo, email) as T
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdviserRecordsScreen(
    viewModel: AdviserRecordsViewModel,
    rbac: RbacEnforcer,
    modifier: Modifier = Modifier,
) {
    if (!rbac.canManageAttendance()) {
        RbacAccessDenied(RbacPermission.ATTENDANCE_MANAGE, rbac, modifier)
        return
    }
    var toDelete by remember { mutableStateOf<AdviserLogRecord?>(null) }
    val canWrite = rbac.canManageAttendance()

    Column(modifier.fillMaxSize()) {
        Text("Class Attendance Records", fontWeight = FontWeight.Black, color = PrimaryDark, fontSize = 16.sp)
        Text("Adviser CRUD — mark, edit, delete attendance & parent contact", fontSize = 12.sp, color = TextSubtitle)
        Spacer(Modifier.height(8.dp))
        OutlinedTextField(
            value = viewModel.filter.query,
            onValueChange = { viewModel.filter = viewModel.filter.copy(query = it); viewModel.refresh() },
            modifier = Modifier.fillMaxWidth(),
            placeholder = { Text("Search student or LRN…") },
            leadingIcon = { Icon(Icons.Default.Search, null) },
            singleLine = true,
        )
        Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
            AttendanceRecordsRepository.ADVISER_STATUSES.forEach { st ->
                FilterChip(
                    selected = viewModel.filter.status == st,
                    onClick = {
                        viewModel.filter = viewModel.filter.copy(status = if (viewModel.filter.status == st) "" else st)
                        viewModel.refresh()
                    },
                    label = { Text(st, fontSize = 11.sp) },
                )
            }
        }
        if (viewModel.isLoading) LinearProgressIndicator(Modifier.fillMaxWidth())
        LazyColumn(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            items(viewModel.logs, key = { it.id }) { log ->
                Card(colors = CardDefaults.cardColors(containerColor = Color.White)) {
                    Column(Modifier.padding(12.dp)) {
                        Text(log.studentName, fontWeight = FontWeight.Bold)
                        Text("${log.date} · ${log.status} · LRN ${log.lrn}", fontSize = 11.sp, color = TextSubtitle)
                        Text("Parent: ${log.parentName} · ${log.parentContact}", fontSize = 11.sp, color = TextSubtitle)
                        if (canWrite) {
                            Row {
                                TextButton(onClick = { viewModel.openEdit(log) }) { Text("Edit") }
                                TextButton(onClick = { toDelete = log }) { Text("Delete", color = ErrorMain) }
                            }
                        }
                    }
                }
            }
        }
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Text(viewModel.statusMessage, fontSize = 11.sp, color = TextSubtitle)
            if (canWrite) {
                FloatingActionButton(onClick = { viewModel.openCreate() }, containerColor = PrimaryMain) {
                    Icon(Icons.Default.Add, null, tint = Color.White)
                }
            }
        }
    }

    if (viewModel.showEditor) {
        AlertDialog(
            onDismissRequest = { viewModel.showEditor = false },
            title = { Text(if (viewModel.editingId > 0L) "Edit attendance" else "New attendance") },
            text = {
                Column(Modifier.verticalScroll(rememberScrollState()), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    var expanded by remember { mutableStateOf(false) }
                    ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = it }) {
                        OutlinedTextField(
                            value = viewModel.students.find { it.id.toLong() == viewModel.editing.studentId }?.name ?: "Select student",
                            onValueChange = {},
                            readOnly = true,
                            label = { Text("Student") },
                            modifier = Modifier.menuAnchor().fillMaxWidth(),
                        )
                        ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                            viewModel.students.forEach { s ->
                                DropdownMenuItem(
                                    text = { Text(s.name) },
                                    onClick = {
                                        viewModel.editing = viewModel.editing.copy(
                                            studentId = s.id.toLong(),
                                            parentName = s.parentName,
                                            parentContact = s.parentContact,
                                        )
                                        expanded = false
                                    },
                                )
                            }
                        }
                    }
                    Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                        AttendanceRecordsRepository.ADVISER_STATUSES.forEach { st ->
                            FilterChip(
                                selected = viewModel.editing.status == st,
                                onClick = { viewModel.editing = viewModel.editing.copy(status = st) },
                                label = { Text(st, fontSize = 10.sp) },
                            )
                        }
                    }
                    OutlinedTextField(
                        value = viewModel.editing.parentName,
                        onValueChange = { viewModel.editing = viewModel.editing.copy(parentName = it) },
                        label = { Text("Parent name") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                    )
                    OutlinedTextField(
                        value = viewModel.editing.parentContact,
                        onValueChange = { viewModel.editing = viewModel.editing.copy(parentContact = it) },
                        label = { Text("Parent contact") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                    )
                    viewModel.formError?.let { Text(it, color = ErrorMain, fontSize = 12.sp) }
                }
            },
            confirmButton = { Button(onClick = { viewModel.save() }) { Text("Save") } },
            dismissButton = { TextButton(onClick = { viewModel.showEditor = false }) { Text("Cancel") } },
        )
    }

    toDelete?.let { r ->
        AlertDialog(
            onDismissRequest = { toDelete = null },
            title = { Text("Delete attendance record?") },
            confirmButton = {
                TextButton(onClick = { viewModel.delete(r); toDelete = null }) { Text("Delete", color = ErrorMain) }
            },
            dismissButton = { TextButton(onClick = { toDelete = null }) { Text("Cancel") } },
        )
    }
}

@Composable
fun rememberAdviserRecordsViewModel(repo: AttendanceRecordsRepository, email: String): AdviserRecordsViewModel {
    return androidx.lifecycle.viewmodel.compose.viewModel(factory = AdviserRecordsViewModel.Factory(repo, email))
}
