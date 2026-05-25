package com.bnhs.edutrack.profile

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.OpenInBrowser
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.bnhs.edutrack.MobileAppRole
import com.bnhs.edutrack.auth.AuthSession
import com.bnhs.edutrack.network.MobileProfileDto
import com.bnhs.edutrack.ui.*
import kotlinx.coroutines.launch

class ProfileViewModel(
    private val repository: ProfileRepository,
    private val initialSession: AuthSession,
    private val mobileRole: MobileAppRole,
) : ViewModel() {
    var profile by mutableStateOf<MobileProfileDto?>(null)
    var firstName by mutableStateOf("")
    var middleName by mutableStateOf("")
    var lastName by mutableStateOf("")
    var suffix by mutableStateOf("")
    var phone by mutableStateOf("")
    var email by mutableStateOf(initialSession.user.email)
    var isLoading by mutableStateOf(false)
    var statusMessage by mutableStateOf("")
    var saveSuccess by mutableStateOf(false)

    var onSessionUpdated: ((AuthSession) -> Unit)? = null

    init {
        load()
    }

    fun load() {
        viewModelScope.launch {
            isLoading = true
            when (val result = repository.loadProfile()) {
                is ProfileResult.Success -> applyDto(result.value)
                is ProfileResult.Error -> statusMessage = result.message
            }
            isLoading = false
        }
    }

    private fun applyDto(dto: MobileProfileDto) {
        profile = dto
        firstName = dto.firstName.orEmpty()
        middleName = dto.middleName.orEmpty()
        lastName = dto.lastName.orEmpty()
        suffix = dto.suffix.orEmpty()
        phone = dto.phone.orEmpty()
        email = dto.email.orEmpty()
        repository.syncSessionFromProfile(dto)
        repository.currentSession()?.let { onSessionUpdated?.invoke(it) }
    }

    fun save() {
        if (firstName.isBlank() || lastName.isBlank() || phone.isBlank()) {
            statusMessage = "First name, last name, and phone are required."
            return
        }
        viewModelScope.launch {
            isLoading = true
            saveSuccess = false
            when (
                val result = repository.saveProfile(
                    firstName = firstName,
                    middleName = middleName,
                    lastName = lastName,
                    suffix = suffix,
                    phone = phone,
                )
            ) {
                is ProfileResult.Success -> {
                    applyDto(result.value)
                    statusMessage = "Profile saved."
                    saveSuccess = true
                }
                is ProfileResult.Error -> statusMessage = result.message
            }
            isLoading = false
        }
    }

    fun roleLabel(): String = when (mobileRole) {
        MobileAppRole.ADMIN -> "Administrator"
        MobileAppRole.ADVISER -> "Adviser"
        MobileAppRole.SECURITY -> "Security"
        MobileAppRole.UNSUPPORTED -> "User"
    }
}

@Composable
fun rememberProfileViewModel(
    repository: ProfileRepository,
    session: AuthSession,
    mobileRole: MobileAppRole,
): ProfileViewModel {
    return remember(repository, session.user.id) {
        ProfileViewModel(repository, session, mobileRole)
    }
}

@Composable
fun ProfileScreen(
    viewModel: ProfileViewModel,
    repository: ProfileRepository,
    onBack: () -> Unit,
    onStatus: (String) -> Unit,
    modifier: Modifier = Modifier,
) {
    val context = LocalContext.current
    val apiBaseUrl = remember(repository) { repository.apiBaseUrl() }
    val scroll = rememberScrollState()
    val roles = viewModel.profile?.roles ?: emptyList()

    LaunchedEffect(viewModel.statusMessage) {
        if (viewModel.statusMessage.isNotBlank()) onStatus(viewModel.statusMessage)
    }

    Column(modifier.fillMaxSize()) {
        Surface(color = LmsColors.Navy) {
            Row(
                Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 4.dp, vertical = 8.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                IconButton(onClick = onBack) {
                    Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back", tint = LmsColors.White)
                }
                Column(Modifier.weight(1f)) {
                    Text("Profile", color = LmsColors.White, fontWeight = androidx.compose.ui.text.font.FontWeight.Bold, fontSize = 18.sp)
                    Text("Your account details", color = LmsColors.White.copy(0.8f), fontSize = 12.sp)
                }
                Icon(Icons.Filled.Person, null, tint = LmsColors.GoldLight, modifier = Modifier.padding(end = 12.dp))
            }
        }

        Column(
            Modifier
                .weight(1f)
                .verticalScroll(scroll)
                .padding(horizontal = 20.dp, vertical = 12.dp),
        ) {
            LmsDashTopBar(breadcrumb = "Profile")

            if (viewModel.isLoading && viewModel.profile == null) {
                Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = LmsColors.Navy)
                }
            }

            LmsDashPanel(
                title = "Account",
                subtitle = "Update your details (synced with EduTrack web)",
            ) {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.padding(bottom = 12.dp)) {
                    roles.forEach { role ->
                        RoleBadge(role.replace('_', ' ').replaceFirstChar { it.uppercase() })
                    }
                }
                LmsOutlinedField(viewModel.firstName, { viewModel.firstName = it }, "First name")
                Spacer(Modifier.height(10.dp))
                LmsOutlinedField(viewModel.middleName, { viewModel.middleName = it }, "Middle name (optional)")
                Spacer(Modifier.height(10.dp))
                LmsOutlinedField(viewModel.lastName, { viewModel.lastName = it }, "Last name")
                Spacer(Modifier.height(10.dp))
                LmsOutlinedField(viewModel.suffix, { viewModel.suffix = it }, "Suffix (optional)")
                Spacer(Modifier.height(10.dp))
                LmsOutlinedField(viewModel.email, {}, "Email", readOnly = true)
                Text(
                    "Email changes are managed by the school admin.",
                    fontSize = 11.sp,
                    color = LmsColors.TextMuted,
                    modifier = Modifier.padding(top = 4.dp, bottom = 10.dp),
                )
                LmsOutlinedField(
                    viewModel.phone,
                    { viewModel.phone = it },
                    "Phone",
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                )
                Spacer(Modifier.height(16.dp))
                LmsPrimaryButton("Save profile", onClick = { viewModel.save() }, enabled = !viewModel.isLoading)
            }

            Spacer(Modifier.height(12.dp))

            LmsDashPanel(
                title = "Connected to web",
                subtitle = "Same profile as the LMS browser app",
            ) {
                Text(
                    "API: $apiBaseUrl",
                    fontSize = 11.sp,
                    color = LmsColors.TextMuted,
                    modifier = Modifier.padding(bottom = 12.dp),
                )
                OutlinedButton(
                    onClick = {
                        val url = viewModel.profile?.webProfileUrl
                            ?: apiBaseUrl
                                .removeSuffix("/api/")
                                .removeSuffix("/api")
                                .let { "$it/settings" }
                        context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
                    },
                    modifier = Modifier.fillMaxWidth(),
                    shape = androidx.compose.foundation.shape.RoundedCornerShape(10.dp),
                ) {
                    Icon(Icons.Filled.OpenInBrowser, null, tint = LmsColors.Navy)
                    Spacer(Modifier.width(8.dp))
                    Text("Open profile on web", color = LmsColors.Navy)
                }
            }
        }
    }
}
