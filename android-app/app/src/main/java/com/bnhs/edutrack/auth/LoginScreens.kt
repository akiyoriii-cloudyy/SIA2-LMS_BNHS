package com.bnhs.edutrack.auth

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

private val PrimaryDark = Color(0xFF1E1B4B)
private val PrimaryMain = Color(0xFF4338CA)
private val SecondaryMain = Color(0xFF06B6D4)
private val ErrorMain = Color(0xFFF43F5E)
private val TextSubtitle = Color(0xFF64748B)

@Composable
fun LoginScreen(
    viewModel: AuthViewModel,
    onForgotPassword: () -> Unit,
) {
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var showPassword by remember { mutableStateOf(false) }
    var showAdvanced by remember { mutableStateOf(false) }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Spacer(modifier = Modifier.height(32.dp))
        Surface(
            modifier = Modifier.size(88.dp),
            shape = RoundedCornerShape(24.dp),
            color = PrimaryMain,
            shadowElevation = 8.dp,
        ) {
            Icon(
                Icons.Default.Lock,
                contentDescription = null,
                tint = Color.White,
                modifier = Modifier.padding(24.dp),
            )
        }
        Spacer(modifier = Modifier.height(20.dp))
        Text("BNHSTrack Pro", fontWeight = FontWeight.Black, fontSize = 24.sp, color = PrimaryDark)
        Text(
            "Sign in with your LMS account",
            color = TextSubtitle,
            fontSize = 14.sp,
            textAlign = TextAlign.Center,
        )
        Spacer(modifier = Modifier.height(28.dp))

        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(20.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White),
            elevation = CardDefaults.cardElevation(defaultElevation = 4.dp),
        ) {
            Column(Modifier.padding(20.dp)) {
                OutlinedTextField(
                    value = email,
                    onValueChange = { email = it },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Email") },
                    placeholder = { Text("admin@bnhs.local") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(
                        keyboardType = KeyboardType.Email,
                        imeAction = ImeAction.Next,
                    ),
                    leadingIcon = { Icon(Icons.Default.Email, null) },
                )
                Spacer(modifier = Modifier.height(12.dp))
                OutlinedTextField(
                    value = password,
                    onValueChange = { password = it },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Password") },
                    singleLine = true,
                    visualTransformation = if (showPassword) {
                        VisualTransformation.None
                    } else {
                        PasswordVisualTransformation()
                    },
                    keyboardOptions = KeyboardOptions(
                        keyboardType = KeyboardType.Password,
                        imeAction = ImeAction.Done,
                    ),
                    keyboardActions = KeyboardActions(
                        onDone = {
                            if (!viewModel.loginInProgress) {
                                viewModel.login(email, password)
                            }
                        },
                    ),
                    leadingIcon = { Icon(Icons.Default.Password, null) },
                    trailingIcon = {
                        IconButton(onClick = { showPassword = !showPassword }) {
                            Icon(
                                if (showPassword) Icons.Default.VisibilityOff else Icons.Default.Visibility,
                                null,
                            )
                        }
                    },
                )

                viewModel.loginError?.let { err ->
                    Spacer(modifier = Modifier.height(12.dp))
                    Text(err, color = ErrorMain, fontSize = 13.sp, lineHeight = 18.sp)
                }

                Spacer(modifier = Modifier.height(20.dp))
                Button(
                    onClick = { viewModel.login(email, password) },
                    modifier = Modifier.fillMaxWidth().height(48.dp),
                    enabled = !viewModel.loginInProgress,
                    shape = RoundedCornerShape(14.dp),
                ) {
                    if (viewModel.loginInProgress) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(22.dp),
                            color = Color.White,
                            strokeWidth = 2.dp,
                        )
                    } else {
                        Text("Sign In", fontWeight = FontWeight.Bold)
                    }
                }

                TextButton(
                    onClick = onForgotPassword,
                    modifier = Modifier.align(Alignment.CenterHorizontally),
                ) {
                    Text("Forgot password?", color = SecondaryMain, fontWeight = FontWeight.SemiBold)
                }
            }
        }

        Spacer(modifier = Modifier.height(16.dp))
        TextButton(onClick = { showAdvanced = !showAdvanced }) {
            Icon(
                if (showAdvanced) Icons.Default.ExpandLess else Icons.Default.ExpandMore,
                null,
                modifier = Modifier.size(18.dp),
            )
            Spacer(modifier = Modifier.width(6.dp))
            Text("Server settings", fontSize = 13.sp)
        }
        if (showAdvanced) {
            OutlinedTextField(
                value = viewModel.apiBaseUrl,
                onValueChange = { viewModel.updateApiBaseUrl(it) },
                modifier = Modifier.fillMaxWidth(),
                label = { Text("API base URL") },
                supportingText = {
                    Text(
                        "Emulator: http://10.0.2.2/LMS_BNHS/public/api/ — Phone: use your PC IP",
                        fontSize = 11.sp,
                    )
                },
                singleLine = true,
            )
            Spacer(modifier = Modifier.height(8.dp))
            OutlinedButton(
                onClick = { viewModel.saveApiBaseUrl() },
                modifier = Modifier.fillMaxWidth(),
            ) {
                Text("Save API URL")
            }
        }

        Spacer(modifier = Modifier.height(24.dp))
        Text(
            "Demo accounts (password: password):\nadmin@bnhs.local · security@bnhs.local · adviser@bnhs.local",
            color = TextSubtitle,
            fontSize = 11.sp,
            textAlign = TextAlign.Center,
            lineHeight = 16.sp,
        )
    }
}

@Composable
fun ForgotPasswordScreen(
    viewModel: AuthViewModel,
    onBack: () -> Unit,
) {
    var email by remember { mutableStateOf("") }

    LaunchedEffect(Unit) {
        viewModel.clearForgotFeedback()
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
    ) {
        IconButton(onClick = onBack) {
            Icon(Icons.Default.ArrowBack, contentDescription = "Back")
        }
        Text("Password recovery", fontWeight = FontWeight.Black, fontSize = 22.sp, color = PrimaryDark)
        Text(
            "Enter your registered email. If it exists in the database, the server sends a reset link.",
            color = TextSubtitle,
            fontSize = 14.sp,
            modifier = Modifier.padding(top = 8.dp, bottom = 24.dp),
        )

        OutlinedTextField(
            value = email,
            onValueChange = { email = it },
            modifier = Modifier.fillMaxWidth(),
            label = { Text("Email") },
            singleLine = true,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
            leadingIcon = { Icon(Icons.Default.Email, null) },
        )

        viewModel.forgotError?.let {
            Spacer(modifier = Modifier.height(12.dp))
            Text(it, color = ErrorMain, fontSize = 13.sp)
        }
        viewModel.forgotMessage?.let {
            Spacer(modifier = Modifier.height(12.dp))
            Text(it, color = Color(0xFF059669), fontSize = 13.sp)
        }

        Spacer(modifier = Modifier.height(20.dp))
        Button(
            onClick = { viewModel.requestPasswordReset(email) },
            modifier = Modifier.fillMaxWidth().height(48.dp),
            enabled = !viewModel.forgotInProgress,
        ) {
            if (viewModel.forgotInProgress) {
                CircularProgressIndicator(modifier = Modifier.size(22.dp), color = Color.White, strokeWidth = 2.dp)
            } else {
                Text("Send reset link", fontWeight = FontWeight.Bold)
            }
        }
    }
}
