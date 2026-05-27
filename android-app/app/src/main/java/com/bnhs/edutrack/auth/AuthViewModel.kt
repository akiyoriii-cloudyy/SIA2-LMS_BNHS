package com.bnhs.edutrack.auth

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.launch

sealed class AuthUiState {
    data object Checking : AuthUiState()
    data object Unauthenticated : AuthUiState()
    data class Authenticated(val session: AuthSession) : AuthUiState()
}

class AuthViewModel(
    private val repository: AuthRepository,
) : ViewModel() {

    var uiState: AuthUiState by mutableStateOf<AuthUiState>(AuthUiState.Checking)
        private set

    var loginError by mutableStateOf<String?>(null)
        private set

    var loginInProgress by mutableStateOf(false)
        private set

    var forgotMessage by mutableStateOf<String?>(null)
        private set

    var forgotError by mutableStateOf<String?>(null)
        private set

    var forgotInProgress by mutableStateOf(false)
        private set

    var apiBaseUrl by mutableStateOf(repository.getApiBaseUrl())
        private set

    var connectionTestMessage by mutableStateOf<String?>(null)
        private set

    var connectionTestInProgress by mutableStateOf(false)
        private set

    var showServerSettings by mutableStateOf(false)
        private set

    init {
        viewModelScope.launch {
            val urlFixed = repository.sanitizeApiBaseUrl()
            apiBaseUrl = repository.getApiBaseUrl()
            if (urlFixed) {
                showServerSettings = true
                connectionTestMessage =
                    "Old server URL removed. Using local XAMPP:\n$apiBaseUrl\nTap Test connection, then Sign In."
            }
            val session = repository.restoreSession()
            uiState = if (session != null) {
                AuthUiState.Authenticated(session)
            } else {
                AuthUiState.Unauthenticated
            }
        }
    }

    fun updateApiBaseUrl(url: String) {
        apiBaseUrl = url
    }

    fun saveApiBaseUrl() {
        repository.setApiBaseUrl(apiBaseUrl)
        apiBaseUrl = repository.getApiBaseUrl()
        connectionTestMessage = "API URL saved."
    }

    fun useEmulatorXamppUrl() {
        apiBaseUrl = SessionStore.EMULATOR_XAMPP_API_URL
        repository.setApiBaseUrl(apiBaseUrl)
        connectionTestMessage = "Set to local XAMPP (emulator). Tap Test connection."
    }

    fun resetApiBaseUrlToDefault() {
        repository.resetApiBaseUrlToDefault()
        apiBaseUrl = repository.getApiBaseUrl()
        connectionTestMessage = "Reset to default: $apiBaseUrl"
    }

    fun testConnection() {
        connectionTestMessage = null
        connectionTestInProgress = true
        repository.setApiBaseUrl(apiBaseUrl)
        viewModelScope.launch {
            when (val result = repository.testConnection()) {
                is ConnectionTestResult.Success ->
                    connectionTestMessage = "Connected to ${result.baseUrl}"
                is ConnectionTestResult.Error ->
                    connectionTestMessage = result.message
            }
            connectionTestInProgress = false
        }
    }

    fun login(email: String, password: String) {
        loginError = null
        loginInProgress = true
        repository.setApiBaseUrl(apiBaseUrl)
        viewModelScope.launch {
            when (val result = repository.login(email, password)) {
                is LoginResult.Success -> {
                    uiState = AuthUiState.Authenticated(result.session)
                    loginError = null
                }
                is LoginResult.Error -> loginError = result.message
            }
            loginInProgress = false
        }
    }

    fun logout() {
        viewModelScope.launch {
            repository.logout()
            uiState = AuthUiState.Unauthenticated
            loginError = null
            forgotMessage = null
            forgotError = null
        }
    }

    fun requestPasswordReset(email: String) {
        forgotMessage = null
        forgotError = null
        forgotInProgress = true
        repository.setApiBaseUrl(apiBaseUrl)
        viewModelScope.launch {
            when (val result = repository.requestPasswordReset(email)) {
                is ForgotPasswordResult.Success -> forgotMessage = result.message
                is ForgotPasswordResult.Error -> forgotError = result.message
            }
            forgotInProgress = false
        }
    }

    fun clearForgotFeedback() {
        forgotMessage = null
        forgotError = null
    }

    class Factory(
        private val repository: AuthRepository,
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(AuthViewModel::class.java)) {
                return AuthViewModel(repository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
