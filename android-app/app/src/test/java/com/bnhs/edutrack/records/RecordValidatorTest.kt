package com.bnhs.edutrack.records

import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class RecordValidatorTest {

    @Test
    fun valid_input_passes() {
        val errors = RecordValidator.validate(
            StudentRecordInput(
                name = "Santos, Ana",
                lrn = "1111110001",
                rfidUid = "RFID-ANA-001",
                gradeLevel = "10",
                section = "Diamond",
                parentName = "Maria Santos",
                parentContact = "09943621529",
            ),
        )
        assertTrue(errors.isEmpty())
    }

    @Test
    fun invalid_lrn_and_phone_fail() {
        val errors = RecordValidator.validate(
            StudentRecordInput(
                name = "Test",
                lrn = "abc",
                rfidUid = "RFID-1",
                gradeLevel = "10",
                section = "A",
                parentName = "Parent",
                parentContact = "123",
            ),
        )
        assertFalse(errors.isEmpty())
    }
}
