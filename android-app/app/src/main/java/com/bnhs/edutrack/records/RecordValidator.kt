package com.bnhs.edutrack.records

object RecordValidator {

    fun validate(input: StudentRecordInput): List<String> {
        val errors = mutableListOf<String>()
        val name = input.name.trim()
        val lrn = input.lrn.trim()
        val rfid = input.rfidUid.trim()
        val grade = input.gradeLevel.trim()
        val section = input.section.trim()
        val parentName = input.parentName.trim()
        val parentContact = input.parentContact.trim()

        if (name.length < 3) errors.add("Student name must be at least 3 characters.")
        if (lrn.isEmpty()) errors.add("LRN is required.")
        else if (!lrn.matches(Regex("^[0-9]{10,12}$"))) errors.add("LRN must be 10–12 digits.")
        if (rfid.isEmpty()) errors.add("RFID UID is required.")
        else if (!rfid.matches(Regex("^[A-Za-z0-9\\-]{4,32}$"))) errors.add("RFID UID: 4–32 letters, numbers, or hyphens.")
        if (grade.isEmpty()) errors.add("Grade level is required.")
        else if (grade.toIntOrNull() == null || grade.toInt() !in 7..12) errors.add("Grade level must be between 7 and 12.")
        if (section.length < 2) errors.add("Section is required.")
        if (input.status !in listOf("ACTIVE", "ARCHIVED")) errors.add("Invalid record status.")
        if (parentName.length < 2) errors.add("Parent/guardian name is required.")
        if (parentContact.isEmpty()) errors.add("Parent contact number is required.")
        else if (!parentContact.matches(Regex("^09[0-9]{9}$"))) errors.add("Contact must be 11 digits starting with 09.")

        return errors
    }
}
