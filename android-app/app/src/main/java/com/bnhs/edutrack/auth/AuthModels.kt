package com.bnhs.edutrack.auth

data class AuthUser(
    val id: Long,
    val name: String,
    val email: String,
    val roles: List<String>,
    val permissions: List<String> = emptyList(),
    val phone: String? = null,
)

data class AuthSession(
    val token: String,
    val tokenType: String,
    val user: AuthUser,
)

sealed class LoginResult {
    data class Success(val session: AuthSession) : LoginResult()
    data class Error(val message: String) : LoginResult()
}

sealed class ForgotPasswordResult {
    data class Success(val message: String) : ForgotPasswordResult()
    data class Error(val message: String) : ForgotPasswordResult()
}
