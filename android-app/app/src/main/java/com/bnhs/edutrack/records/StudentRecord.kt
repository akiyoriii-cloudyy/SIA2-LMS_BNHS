package com.bnhs.edutrack.records

data class StudentRecord(
    val id: Long = 0L,
    val name: String,
    val lrn: String,
    val rfidUid: String,
    val gradeLevel: String,
    val section: String,
    val status: String = "ACTIVE",
    val sex: String = "",
    val parentId: Long = 0L,
    val parentName: String,
    val parentContact: String,
)

data class StudentRecordInput(
    val id: Long = 0L,
    val name: String = "",
    val lrn: String = "",
    val rfidUid: String = "",
    val gradeLevel: String = "",
    val section: String = "",
    val status: String = "ACTIVE",
    val sex: String = "",
    val parentName: String = "",
    val parentContact: String = "",
)

data class RecordsFilter(
    val query: String = "",
    val gradeLevel: String = "",
    val section: String = "",
    val status: String = "",
)

sealed class RecordsResult<out T> {
    data class Success<T>(val data: T) : RecordsResult<T>()
    data class Error(val message: String) : RecordsResult<Nothing>()
}
