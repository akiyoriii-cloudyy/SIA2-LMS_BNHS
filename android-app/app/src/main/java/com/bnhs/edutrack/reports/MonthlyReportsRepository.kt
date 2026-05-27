package com.bnhs.edutrack.reports

import android.content.Context
import com.bnhs.edutrack.auth.SessionStore
import com.bnhs.edutrack.network.ApiClient
import com.bnhs.edutrack.network.LmsApiService
import com.bnhs.edutrack.network.MonthlyReportDetailDto
import com.bnhs.edutrack.network.MonthlyReportSummaryDto

sealed class ReportsResult<out T> {
    data class Success<T>(val value: T) : ReportsResult<T>()
    data class Error(val message: String) : ReportsResult<Nothing>()
}

class MonthlyReportsRepository(context: Context) {

    private val sessionStore = SessionStore(context.applicationContext)
    private val api: LmsApiService = ApiClient.createLmsApi(sessionStore)

    suspend fun listReports(schoolYearId: Long? = null): ReportsResult<List<MonthlyReportSummaryDto>> {
        val auth = sessionStore.bearerAuthorization()
            ?: return ReportsResult.Error("Not signed in.")
        return try {
            val response = api.listMonthlyReports(auth, schoolYearId)
            if (response.isSuccessful) {
                ReportsResult.Success(response.body()?.data.orEmpty())
            } else {
                ReportsResult.Error(ApiClient.parseErrorMessage(response))
            }
        } catch (e: Exception) {
            ReportsResult.Error(e.localizedMessage ?: "Could not load reports.")
        }
    }

    suspend fun getReport(reportId: Long): ReportsResult<MonthlyReportDetailDto> {
        val auth = sessionStore.bearerAuthorization()
            ?: return ReportsResult.Error("Not signed in.")
        return try {
            val response = api.getMonthlyReport(auth, reportId)
            if (response.isSuccessful) {
                val data = response.body()?.data
                if (data != null) {
                    ReportsResult.Success(data)
                } else {
                    ReportsResult.Error("Report not found.")
                }
            } else {
                ReportsResult.Error(ApiClient.parseErrorMessage(response))
            }
        } catch (e: Exception) {
            ReportsResult.Error(e.localizedMessage ?: "Could not load report.")
        }
    }

    fun webPortalUrl(): String {
        val base = sessionStore.getApiBaseUrl()
            .removeSuffix("/api/")
            .removeSuffix("/api")
            .removeSuffix("/")
        return "$base/attendance-reports"
    }

    companion object {
        @Volatile
        private var instance: MonthlyReportsRepository? = null

        fun get(context: Context): MonthlyReportsRepository {
            return instance ?: synchronized(this) {
                instance ?: MonthlyReportsRepository(context).also { instance = it }
            }
        }
    }
}
