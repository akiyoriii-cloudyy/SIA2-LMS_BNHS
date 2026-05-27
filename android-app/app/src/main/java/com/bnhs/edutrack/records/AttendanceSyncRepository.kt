package com.bnhs.edutrack.records

import android.content.Context
import android.provider.Settings
import com.bnhs.edutrack.AttendanceRecord
import com.bnhs.edutrack.auth.SessionStore
import com.bnhs.edutrack.data.BnhsDatabase
import com.bnhs.edutrack.data.ParentEntity
import com.bnhs.edutrack.data.StudentEntity
import com.bnhs.edutrack.network.ApiClient
import com.bnhs.edutrack.network.LmsApiService
import com.bnhs.edutrack.network.SyncAttendanceRecordDto
import com.bnhs.edutrack.network.SyncAttendanceRequest
import com.bnhs.edutrack.security.SensitiveDataGuard
import java.time.format.DateTimeFormatter
import java.util.UUID

sealed class AttendanceSyncResult {
    data class Success(val message: String) : AttendanceSyncResult()
    data class Error(val message: String) : AttendanceSyncResult()
    data object Skipped : AttendanceSyncResult()
}

class AttendanceSyncRepository(context: Context) {

    private val appContext = context.applicationContext
    private val sessionStore = SessionStore(appContext)
    private val api: LmsApiService = ApiClient.createLmsApi(sessionStore)
    private val db = BnhsDatabase.get(appContext)
    private val guard = SensitiveDataGuard.get(appContext)
    private val isoDate = DateTimeFormatter.ISO_LOCAL_DATE

    suspend fun syncAdviserRosterFromServer(): AttendanceSyncResult {
        val auth = sessionStore.bearerAuthorization()
            ?: return AttendanceSyncResult.Error("Not signed in.")

        return try {
            val response = api.adviserRoster(auth)
            if (!response.isSuccessful) {
                return AttendanceSyncResult.Error(ApiClient.parseErrorMessage(response))
            }

            val rows = response.body()?.data.orEmpty()
            if (rows.isEmpty()) {
                return AttendanceSyncResult.Error("No students found on the server for your assigned sections.")
            }

            pruneLocalOnlyStudents()

            val syncedEnrollmentIds = mutableSetOf<Long>()
            var synced = 0
            rows.forEach { row ->
                val enrollmentId = row.enrollmentId ?: return@forEach
                val serverStudentId = row.studentId ?: return@forEach
                val name = row.studentName?.trim().orEmpty().ifBlank { "Student" }
                val lrn = row.lrn?.trim().orEmpty()
                val serverRfid = row.rfidUid?.trim().orEmpty()
                val section = row.sectionName?.trim().orEmpty()
                val grade = row.gradeLevel?.toString().orEmpty()
                val guardianName = row.primaryGuardian?.name?.trim().orEmpty()
                val guardianPhone = row.primaryGuardian?.phone?.trim().orEmpty()

                syncedEnrollmentIds += enrollmentId

                val existing = db.studentDao().getAll().firstOrNull { student ->
                    student.enrollmentId == enrollmentId
                } ?: db.studentDao().getAll().firstOrNull { student ->
                    student.serverStudentId == serverStudentId && student.enrollmentId == null
                } ?: if (lrn.isNotEmpty()) {
                    db.studentDao().findByLrn(lrn)
                } else {
                    null
                }

                val rfid = resolveUniqueRfid(
                    serverRfid = serverRfid,
                    enrollmentId = enrollmentId,
                    serverStudentId = serverStudentId,
                    excludeStudentId = existing?.id ?: 0L,
                )

                val studentId = if (existing == null) {
                    db.studentDao().insert(
                        StudentEntity(
                            name = name,
                            lrn = lrn.ifBlank { "ENR-$enrollmentId" },
                            rfidUid = rfid,
                            gradeLevel = grade,
                            section = section,
                            enrollmentId = enrollmentId,
                            serverStudentId = serverStudentId,
                        ),
                    )
                } else {
                    db.studentDao().update(
                        existing.copy(
                            name = name,
                            lrn = lrn.ifBlank { existing.lrn },
                            rfidUid = rfid,
                            gradeLevel = grade.ifBlank { existing.gradeLevel },
                            section = section.ifBlank { existing.section },
                            enrollmentId = enrollmentId,
                            serverStudentId = serverStudentId,
                            updatedAt = System.currentTimeMillis(),
                        ),
                    )
                    existing.id
                }

                if (guardianName.isNotBlank() || guardianPhone.isNotBlank()) {
                    val parent = db.parentDao().primaryForStudent(studentId)
                    if (parent == null) {
                        db.parentDao().insert(
                            guard.parentForStorage(
                                ParentEntity(
                                    studentId = studentId,
                                    name = guardianName.ifBlank { "Guardian" },
                                    contact = guardianPhone,
                                    isPrimary = true,
                                ),
                            ),
                        )
                    } else {
                        db.parentDao().updateContactInfo(
                            id = parent.id,
                            name = guardianName.ifBlank { parent.name },
                            contact = guard.encryptField(guardianPhone.ifBlank { parent.contact }),
                        )
                    }
                }

                synced++
            }

            if (synced == 0) {
                return AttendanceSyncResult.Error(
                    "Server returned a roster but no students could be imported. Sign out and sign in again, or contact the administrator.",
                )
            }

            pruneStudentsNotOnServer(syncedEnrollmentIds)

            AttendanceSyncResult.Success("Synced $synced student(s) from server.")
        } catch (e: Exception) {
            AttendanceSyncResult.Error(e.localizedMessage ?: "Could not sync roster from server.")
        }
    }

    suspend fun syncAttendanceRecord(record: AttendanceRecord, studentId: Int): AttendanceSyncResult {
        val auth = sessionStore.bearerAuthorization()
            ?: return AttendanceSyncResult.Skipped

        val student = db.studentDao().getById(studentId.toLong())
            ?: return AttendanceSyncResult.Skipped

        val enrollmentId = student.enrollmentId
            ?: return AttendanceSyncResult.Skipped

        val serverStatus = when (record.status.uppercase()) {
            "PRESENT" -> "present"
            "ABSENT" -> "absent"
            "LATE" -> "late"
            "EXCUSED" -> "excused"
            else -> return AttendanceSyncResult.Skipped
        }

        if (record.loggedBy != "ADVISER" && record.loggedBy != "TEACHER") {
            return AttendanceSyncResult.Skipped
        }

        return try {
            val response = api.syncAttendance(
                auth,
                SyncAttendanceRequest(
                    deviceId = deviceId(),
                    batchUuid = UUID.randomUUID().toString(),
                    records = listOf(
                        SyncAttendanceRecordDto(
                            enrollmentId = enrollmentId,
                            attendanceDate = isoDate.format(record.date),
                            status = serverStatus,
                        ),
                    ),
                ),
            )
            if (response.isSuccessful) {
                AttendanceSyncResult.Success(response.body()?.message ?: "Attendance synced.")
            } else {
                AttendanceSyncResult.Error(ApiClient.parseErrorMessage(response))
            }
        } catch (e: Exception) {
            AttendanceSyncResult.Error(e.localizedMessage ?: "Could not sync attendance.")
        }
    }

    /** Removes demo/offline rows (no server enrollment) so they cannot collide on unique RFID. */
    private suspend fun pruneLocalOnlyStudents() {
        db.studentDao().getAll()
            .filter { it.enrollmentId == null }
            .forEach { student ->
                db.attendanceDao().deleteForStudent(student.id)
                db.studentDao().delete(student)
            }
    }

    private suspend fun resolveUniqueRfid(
        serverRfid: String,
        enrollmentId: Long,
        serverStudentId: Long,
        excludeStudentId: Long,
    ): String {
        val normalized = serverRfid.uppercase()
        val candidates = buildList {
            if (normalized.isNotEmpty()) add(normalized)
            add("RFID-ENR-$enrollmentId")
            add("RFID-SRV-$serverStudentId")
        }.distinct()

        for (candidate in candidates) {
            if (db.studentDao().countByRfid(candidate, excludeStudentId) == 0) {
                return candidate
            }
        }

        var suffix = 1
        while (true) {
            val fallback = "RFID-ENR-$enrollmentId-$suffix"
            if (db.studentDao().countByRfid(fallback, excludeStudentId) == 0) {
                return fallback
            }
            suffix++
        }
    }

    private suspend fun pruneStudentsNotOnServer(syncedEnrollmentIds: Set<Long>) {
        val stale = db.studentDao().getAll().filter { student ->
            val enrollmentId = student.enrollmentId
            enrollmentId == null || enrollmentId !in syncedEnrollmentIds
        }
        stale.forEach { student ->
            db.attendanceDao().deleteForStudent(student.id)
            db.studentDao().delete(student)
        }
    }

    private fun deviceId(): String {
        return Settings.Secure.getString(appContext.contentResolver, Settings.Secure.ANDROID_ID)
            ?: "edutrack-device"
    }

    companion object {
        @Volatile
        private var instance: AttendanceSyncRepository? = null

        fun get(context: Context): AttendanceSyncRepository {
            return instance ?: synchronized(this) {
                instance ?: AttendanceSyncRepository(context).also { instance = it }
            }
        }
    }
}
