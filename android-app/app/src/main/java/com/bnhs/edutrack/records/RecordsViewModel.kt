package com.bnhs.edutrack.records

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.bnhs.edutrack.Student
import kotlinx.coroutines.launch

class RecordsViewModel(
    private val repository: RecordsRepository,
    private val actorEmail: String,
    private val seedOnInit: Boolean = true,
) : ViewModel() {

    var records by mutableStateOf<List<StudentRecord>>(emptyList())
        private set

    var filter by mutableStateOf(RecordsFilter())
        private set

    var gradeOptions by mutableStateOf<List<String>>(emptyList())
        private set

    var sectionOptions by mutableStateOf<List<String>>(emptyList())
        private set

    var isLoading by mutableStateOf(false)
        private set

    var statusMessage by mutableStateOf("")
        private set

    var formError by mutableStateOf<String?>(null)
        private set

    var showEditor by mutableStateOf(false)
        private set

    var editingInput by mutableStateOf(StudentRecordInput())
        private set

    var onRecordsChanged: ((List<Student>) -> Unit)? = null

    init {
        viewModelScope.launch {
            if (seedOnInit) {
                repository.ensureSeedData()
            }
            reloadFilterOptions()
            refresh()
        }
    }

    fun updateFilter(transform: (RecordsFilter) -> RecordsFilter) {
        filter = transform(filter)
        refresh()
    }

    fun refresh() {
        viewModelScope.launch {
            isLoading = true
            records = repository.listFiltered(filter)
            isLoading = false
            statusMessage = "${records.size} record(s) found."
            notifyAppStudents()
        }
    }

    fun openCreate() {
        formError = null
        editingInput = StudentRecordInput()
        showEditor = true
    }

    fun openEdit(record: StudentRecord) {
        formError = null
        editingInput = StudentRecordInput(
            id = record.id,
            name = record.name,
            lrn = record.lrn,
            rfidUid = record.rfidUid,
            gradeLevel = record.gradeLevel,
            section = record.section,
            status = record.status,
            sex = record.sex,
            parentName = record.parentName,
            parentContact = record.parentContact,
        )
        showEditor = true
    }

    fun closeEditor() {
        showEditor = false
        formError = null
    }

    fun updateEditor(input: StudentRecordInput) {
        editingInput = input
    }

    fun saveEditor() {
        viewModelScope.launch {
            isLoading = true
            val result = if (editingInput.id > 0L) {
                repository.update(editingInput, actorEmail)
            } else {
                repository.create(editingInput, actorEmail)
            }
            isLoading = false
            when (result) {
                is RecordsResult.Success -> {
                    showEditor = false
                    formError = null
                    statusMessage = if (editingInput.id > 0L) "Record updated." else "Record created."
                    reloadFilterOptions()
                    refresh()
                }
                is RecordsResult.Error -> formError = result.message
            }
        }
    }

    fun deleteRecord(record: StudentRecord) {
        viewModelScope.launch {
            isLoading = true
            when (val result = repository.delete(record.id, actorEmail)) {
                is RecordsResult.Success -> {
                    statusMessage = "Record deleted: ${record.name}"
                    refresh()
                }
                is RecordsResult.Error -> statusMessage = result.message
            }
            isLoading = false
        }
    }

    private suspend fun reloadFilterOptions() {
        val (grades, sections) = repository.filterOptions()
        gradeOptions = grades
        sectionOptions = sections
    }

    private suspend fun notifyAppStudents() {
        onRecordsChanged?.invoke(repository.loadAppStudents())
    }

    class Factory(
        private val repository: RecordsRepository,
        private val actorEmail: String,
        private val seedOnInit: Boolean = true,
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(RecordsViewModel::class.java)) {
                return RecordsViewModel(repository, actorEmail, seedOnInit) as T
            }
            throw IllegalArgumentException("Unknown ViewModel")
        }
    }
}
