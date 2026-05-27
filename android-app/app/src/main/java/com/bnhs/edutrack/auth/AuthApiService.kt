package com.bnhs.edutrack.auth

import com.google.gson.annotations.SerializedName
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST

interface AuthApiService {

    @POST("auth/login")
    suspend fun login(@Body body: LoginRequest): Response<LoginResponse>

    @POST("auth/logout")
    suspend fun logout(@Header("Authorization") authorization: String): Response<MessageResponse>

    @POST("auth/forgot-password")
    suspend fun forgotPassword(@Body body: ForgotPasswordRequest): Response<MessageResponse>

    @GET("auth/rbac")
    suspend fun rbacProfile(@Header("Authorization") authorization: String): Response<RbacProfileResponse>
}

data class LoginRequest(
    val email: String,
    val password: String,
)

data class LoginResponse(
    val token: String?,
    @SerializedName("token_type") val tokenType: String?,
    val user: LoginUserResponse?,
    val message: String?,
)

data class LoginUserResponse(
    val id: Long?,
    val name: String?,
    val email: String?,
    val roles: List<String>?,
    val permissions: List<String>?,
)

data class RbacProfileResponse(
    val rbac: RbacMatrixResponse?,
    val user: LoginUserResponse?,
)

data class RbacMatrixResponse(
    val hierarchy: List<RbacRoleTierResponse>?,
    @com.google.gson.annotations.SerializedName("your_roles") val yourRoles: List<String>?,
    @com.google.gson.annotations.SerializedName("your_permissions") val yourPermissions: List<String>?,
)

data class RbacRoleTierResponse(
    val role: String?,
    val level: Int?,
    val label: String?,
    val description: String?,
)

data class ForgotPasswordRequest(val email: String)

data class MessageResponse(val message: String?)

data class ErrorBody(val message: String?, val errors: Map<String, List<String>>?)
