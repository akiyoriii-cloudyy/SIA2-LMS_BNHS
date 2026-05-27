package com.bnhs.edutrack.sync

import android.content.Context
import com.bnhs.edutrack.auth.SessionStore
import com.bnhs.edutrack.data.BnhsDatabase
import com.bnhs.edutrack.data.ParentEntity
import com.bnhs.edutrack.data.StudentEntity
import com.bnhs.edutrack.network.ApiClient
import com.bnhs.edutrack.network.LmsApiService
import com.bnhs.edutrack.network.RosterStudentDto
import com.bnhs.edutrack.security.SensitiveDataGuard
import androidx.room.withTransaction

sealed class RosterSyncResult {
    data class Success(val studentCount: Int, val sectionNames: String) : RosterSyncResult()
    data class Error(val message: String) : RosterSyncResult()
}

class RosterSyncRepository private constructor(
    private val context: Context,
    private val db: BnhsDatabase,
    private val sessionStore: SessionStore,
    private val guard: SensitiveDataGuard,
) {

    private val api: LmsApiService = ApiClient.createLmsApi(sessionStore)

    suspend fun syncAdviserRoster(): RosterSyncResult {
        val auth = sessionStore.bearerAuthorization()
            ?: return RosterSyncResult.Error("Not signed in.")

        return try {
            val bootstrapResponse = api.bootstrap(auth)
            if (!bootstrapResponse.isSuccessful) {
                return RosterSyncResult.Error(ApiClient.parseErrorMessage(bootstrapResponse))
            }

            val bootstrap = bootstrapResponse.body()
            val schoolYearId = bootstrap?.activeSchoolYear?.id
                ?: return RosterSyncResult.Error("No active school year on the server.")

            val sections = bootstrap.assignedSections.orEmpty().filter { it.id != null }
            if (sections.isEmpty()) {
                return RosterSyncResult.Error("No class section assigned to your adviser account.")
            }

            val rosterRows = mutableListOf<RosterStudentDto>()
            for (section in sections) {
                val sectionId = section.id ?: continue
                val rosterResponse = api.roster(auth, sectionId, schoolYearId)
                if (!rosterResponse.isSuccessful) {
                    return RosterSyncResult.Error(ApiClient.parseErrorMessage(rosterResponse))
                }
                rosterRows.addAll(rosterResponse.body()?.data.orEmpty())
            }

            val now = System.currentTimeMillis()
            db.withTransaction {
                db.studentDao().deleteAll()
                rosterRows.forEach { row ->
                    upsertRosterRow(row, now)
                }
            }

            val sectionLabel = sections.mapNotNull { it.name }.joinToString(", ")
            RosterSyncResult.Success(
                studentCount = rosterRows.size,
                sectionNames = sectionLabel,
            )
        } catch (e: Exception) {
            RosterSyncResult.Error(e.localizedMessage ?: "Could not sync class roster.")
        }
    }

    private suspend fun upsertRosterRow(row: RosterStudentDto, now: Long) {
        val enrollmentId = row.enrollmentId ?: return
        val displayName = row.studentName?.trim().orEmpty().ifBlank { "Student" }
        val lrn = row.lrn?.trim().orEmpty().ifBlank { "SYN-$enrollmentId" }
        val rfid = "ENR-$enrollmentId"
        val existing = db.studentDao().findByEnrollmentId(enrollmentId)
        val guardianName = row.primaryGuardian?.name?.trim().orEmpty()
        val guardianPhone = row.primaryGuardian?.phone?.trim().orEmpty()

        val studentId = if (existing != null) {
            db.studentDao().update(
                existing.copy(
                    name = displayName,
                    lrn = lrn,
                    rfidUid = existing.rfidUid.ifBlank { rfid },
                    status = "ACTIVE",
                    updatedAt = now,
                    serverStudentId = row.studentId,
                ),
            )
            existing.id
        } else {
            db.studentDao().insert(
                StudentEntity(
                    name = displayName,
                    lrn = lrn,
                    rfidUid = rfid,
                    status = "ACTIVE",
                    enrollmentId = enrollmentId,
                    serverStudentId = row.studentId,
                    createdAt = now,
                    updatedAt = now,
                ),
            )
        }

        if (studentId <= 0L) return

        val parent = db.parentDao().primaryForStudent(studentId)
        if (parent == null && (guardianName.isNotBlank() || guardianPhone.isNotBlank())) {
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
        } else if (parent != null && (guardianName.isNotBlank() || guardianPhone.isNotBlank())) {
            db.parentDao().updateContactInfo(
                id = parent.id,
                name = guardianName.ifBlank { parent.name },
                contact = if (guardianPhone.isNotBlank()) guard.encryptField(guardianPhone) else parent.contact,
            )
        }
    }

    companion object {
        @Volatile
        private var instance: RosterSyncRepository? = null

        fun get(context: Context): RosterSyncRepository {
            return instance ?: synchronized(this) {
                instance ?: RosterSyncRepository(
                    context.applicationContext,
                    BnhsDatabase.get(context),
                    SessionStore(context.applicationContext),
                    SensitiveDataGuard.get(context),
                ).also { instance = it }
            }
        }
    }
}
