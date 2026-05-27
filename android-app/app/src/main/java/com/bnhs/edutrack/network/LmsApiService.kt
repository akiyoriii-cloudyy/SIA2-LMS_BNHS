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

    @GET("mobile/bootstrap")
    suspend fun bootstrap(
        @Header("Authorization") authorization: String,
    ): Response<MobileBootstrapResponse>

    @GET("mobile/roster")
    suspend fun roster(
        @Header("Authorization") authorization: String,
        @Query("section_id") sectionId: Long,
        @Query("school_year_id") schoolYearId: Long? = null,
    ): Response<MobileRosterResponse>

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
    ): Response<MonthlyReportDetailResponse>

    @POST("mobile/attendance/monthly-reports/generate")
    suspend fun generateMonthlyReport(
        @Header("Authorization") authorization: String,
        @Body body: GenerateMonthlyReportRequest,
    ): Response<MonthlyReportsGenerateResponse>
}

data class MobileBootstrapResponse(
    @SerializedName("active_school_year") val activeSchoolYear: BootstrapSchoolYearDto?,
    @SerializedName("assigned_sections") val assignedSections: List<BootstrapSectionDto>?,
)

data class BootstrapSchoolYearDto(
    val id: Long?,
    val name: String?,
    @SerializedName("is_active") val isActive: Boolean?,
)

data class BootstrapSectionDto(
    val id: Long?,
    val name: String?,
    @SerializedName("grade_level") val gradeLevel: Int?,
)

data class MobileRosterResponse(
    @SerializedName("week_start") val weekStart: String?,
    val data: List<RosterStudentDto>?,
)

data class RosterStudentDto(
    @SerializedName("enrollment_id") val enrollmentId: Long?,
    @SerializedName("student_id") val studentId: Long?,
    @SerializedName("student_name") val studentName: String?,
    val lrn: String?,
    @SerializedName("primary_guardian") val primaryGuardian: RosterGuardianDto?,
)

data class RosterGuardianDto(
    val name: String?,
    val phone: String?,
)

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

data class GenerateMonthlyReportRequest(
    @SerializedName("report_year") val reportYear: Int,
    @SerializedName("report_month") val reportMonth: Int,
    @SerializedName("school_year_id") val schoolYearId: Long? = null,
    @SerializedName("section_id") val sectionId: Long? = null,
)

data class MonthlyReportsGenerateResponse(
    val message: String?,
    val data: List<MonthlyReportSummaryDto>?,
    @SerializedName("web_portal_url") val webPortalUrl: String?,
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
    @SerializedName("excel_url") val excelUrl: String?,
    @SerializedName("reports_index_url") val reportsIndexUrl: String?,
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
    @SerializedName("excel_url") val excelUrl: String?,
    @SerializedName("reports_index_url") val reportsIndexUrl: String?,
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
)
