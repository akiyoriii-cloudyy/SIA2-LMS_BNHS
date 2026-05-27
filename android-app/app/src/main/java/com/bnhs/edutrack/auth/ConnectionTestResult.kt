package com.bnhs.edutrack.auth

sealed class ConnectionTestResult {
    data class Success(val baseUrl: String) : ConnectionTestResult()
    data class Error(val message: String) : ConnectionTestResult()
}
