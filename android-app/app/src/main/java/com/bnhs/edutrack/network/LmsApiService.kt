package com.bnhs.edutrack.network

import com.google.gson.annotations.SerializedName
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query

interface LmsApiService {

    @GET("mobile/profile")
    suspend fun getProfile(
        @Header("Authorization") authorization: String,
    ): Response<MobileProfileResponse>

    @PUT("mobile/profile")
    suspend fun updateProfile(
        @Header("Authorization") authorization: String,
        @Body body: UpdateProfileRequest,
    ): Response<MobileProfileUpdateResponse>

    @GET("mobile/attendance/monthly-reports")
    suspend fun listMonthlyReports(
        @Header("Authorization") authorization: String,
        @Query("school_year_id") schoolYearId: Long? = null,
    ): Response<MonthlyReportsListResponse>

    @GET("mobile/attendance/monthly-reports/{id}")
    suspend fun getMonthlyReport(
        @Header("Authorization") authorization: String,
        @Path("id") reportId: Long,
        @Query("refresh") refresh: Int? = null,
    ): Response<MonthlyReportDetailResponse>

    @POST("mobile/attendance/monthly-reports/{id}/generate")
    suspend fun generateMonthlyReport(
        @Header("Authorization") authorization: String,
        @Path("id") reportId: Long,
        @Query("send_email") sendEmail: Int = 1,
    ): Response<MonthlyReportGenerateResponse>

    @GET("mobile/adviser-roster")
    suspend fun adviserRoster(
        @Header("Authorization") authorization: String,
        @Query("school_year_id") schoolYearId: Long? = null,
    ): Response<AdviserRosterResponse>

    @POST("mobile/sync/attendance")
    suspend fun syncAttendance(
        @Header("Authorization") authorization: String,
        @Body body: SyncAttendanceRequest,
    ): Response<SyncAttendanceResponse>
}

data class MobileProfileResponse(
    val data: MobileProfileDto?,
)

data class MobileProfileUpdateResponse(
    val message: String?,
    val data: MobileProfileDto?,
)

data class MobileProfileDto(
    val id: Long?,
    val name: String?,
    val email: String?,
    val phone: String?,
    @SerializedName("first_name") val firstName: String?,
    @SerializedName("middle_name") val middleName: String?,
    @SerializedName("last_name") val lastName: String?,
    val suffix: String?,
    val roles: List<String>?,
    val permissions: List<String>?,
    @SerializedName("web_profile_url") val webProfileUrl: String?,
)

data class UpdateProfileRequest(
    @SerializedName("first_name") val firstName: String,
    @SerializedName("middle_name") val middleName: String?,
    @SerializedName("last_name") val lastName: String,
    val suffix: String?,
    val phone: String,
)

data class MonthlyReportsListResponse(
    val data: List<MonthlyReportSummaryDto>?,
    @SerializedName("web_portal_url") val webPortalUrl: String?,
)

data class MonthlyReportDetailResponse(
    val data: MonthlyReportDetailDto?,
)

data class MonthlyReportGenerateResponse(
    val message: String?,
    val data: MonthlyReportDetailDto?,
)

data class MonthlyReportSummaryDto(
    val id: Long?,
    @SerializedName("report_year") val reportYear: Int?,
    @SerializedName("report_month") val reportMonth: Int?,
    @SerializedName("period_label") val periodLabel: String?,
    val status: String?,
    @SerializedName("school_days_total") val schoolDaysTotal: Int?,
    val notes: String?,
    @SerializedName("generated_at") val generatedAt: String?,
    @SerializedName("emailed_at") val emailedAt: String?,
    val section: ReportSectionDto?,
    @SerializedName("school_year") val schoolYear: ReportSchoolYearDto?,
    @SerializedName("lines_count") val linesCount: Int?,
    @SerializedName("total_absent_days") val totalAbsentDays: Int?,
    @SerializedName("web_url") val webUrl: String?,
    @SerializedName("print_url") val printUrl: String?,
)

data class MonthlyReportDetailDto(
    val id: Long?,
    @SerializedName("report_year") val reportYear: Int?,
    @SerializedName("report_month") val reportMonth: Int?,
    @SerializedName("period_label") val periodLabel: String?,
    val status: String?,
    @SerializedName("school_days_total") val schoolDaysTotal: Int?,
    val notes: String?,
    @SerializedName("generated_at") val generatedAt: String?,
    @SerializedName("emailed_at") val emailedAt: String?,
    val section: ReportSectionDto?,
    @SerializedName("school_year") val schoolYear: ReportSchoolYearDto?,
    @SerializedName("lines_count") val linesCount: Int?,
    @SerializedName("total_absent_days") val totalAbsentDays: Int?,
    @SerializedName("web_url") val webUrl: String?,
    @SerializedName("print_url") val printUrl: String?,
    val lines: List<MonthlyReportLineDto>?,
)

data class ReportSectionDto(
    val id: Long?,
    val name: String?,
    @SerializedName("grade_level") val gradeLevel: Int?,
)

data class ReportSchoolYearDto(
    val id: Long?,
    val name: String?,
)

data class AdviserRosterResponse(
    @SerializedName("school_year_id") val schoolYearId: Long?,
    val data: List<AdviserRosterRowDto>?,
)

data class AdviserRosterRowDto(
    @SerializedName("enrollment_id") val enrollmentId: Long?,
    @SerializedName("student_id") val studentId: Long?,
    @SerializedName("student_name") val studentName: String?,
    val lrn: String?,
    @SerializedName("rfid_uid") val rfidUid: String?,
    @SerializedName("section_name") val sectionName: String?,
    @SerializedName("grade_level") val gradeLevel: Int?,
    @SerializedName("primary_guardian") val primaryGuardian: AdviserGuardianDto?,
)

data class AdviserGuardianDto(
    val name: String?,
    val phone: String?,
)

data class SyncAttendanceRequest(
    @SerializedName("device_id") val deviceId: String,
    @SerializedName("batch_uuid") val batchUuid: String,
    val records: List<SyncAttendanceRecordDto>,
)

data class SyncAttendanceRecordDto(
    @SerializedName("enrollment_id") val enrollmentId: Long,
    @SerializedName("attendance_date") val attendanceDate: String,
    val status: String,
)

data class SyncAttendanceResponse(
    val message: String?,
)

data class MonthlyReportLineDto(
    val id: Long?,
    @SerializedName("enrollment_id") val enrollmentId: Long?,
    @SerializedName("student_name") val studentName: String?,
    val lrn: String?,
    @SerializedName("school_days") val schoolDays: Int?,
    @SerializedName("present_days") val presentDays: Int?,
    @SerializedName("absent_days") val absentDays: Int?,
    @SerializedName("late_days") val lateDays: Int?,
    @SerializedName("excused_days") val excusedDays: Int?,
    val remarks: String?,
    @SerializedName("attendance_by_day") val attendanceByDay: Map<String, String>?,
)
