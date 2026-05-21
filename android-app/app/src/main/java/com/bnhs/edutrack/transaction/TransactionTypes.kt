package com.bnhs.edutrack.transaction

object TxStatus {
    const val PENDING = "PENDING"
    const val COMMITTED = "COMMITTED"
    const val ROLLED_BACK = "ROLLED_BACK"
}

object TxOperation {
    const val STUDENT_CREATE = "STUDENT_CREATE"
    const val STUDENT_UPDATE = "STUDENT_UPDATE"
    const val STUDENT_DELETE = "STUDENT_DELETE"
    const val ATTENDANCE_GATE_UPSERT = "ATTENDANCE_GATE_UPSERT"
    const val ATTENDANCE_ADVISER_UPSERT = "ATTENDANCE_ADVISER_UPSERT"
    const val ATTENDANCE_DELETE = "ATTENDANCE_DELETE"
    const val GUARDIAN_UPDATE = "GUARDIAN_UPDATE"
}

object TxEntityType {
    const val STUDENT = "STUDENT"
    const val ATTENDANCE = "ATTENDANCE"
    const val PARENT = "PARENT"
}
