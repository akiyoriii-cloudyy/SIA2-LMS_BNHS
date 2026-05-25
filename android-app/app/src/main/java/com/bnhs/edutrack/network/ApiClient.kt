package com.bnhs.edutrack.network

import com.bnhs.edutrack.auth.AuthApiService
import com.bnhs.edutrack.auth.ErrorBody
import com.bnhs.edutrack.auth.SessionStore
import okhttp3.Interceptor
import com.google.gson.Gson
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiClient {

    private val gson = Gson()

    fun createAuthApi(sessionStore: SessionStore): AuthApiService {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BASIC
        }
        val client = OkHttpClient.Builder()
            .connectTimeout(20, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .addInterceptor(logging)
            .build()

        val retrofit = Retrofit.Builder()
            .baseUrl(sessionStore.getApiBaseUrl())
            .client(client)
            .addConverterFactory(GsonConverterFactory.create(gson))
            .build()

        return retrofit.create(AuthApiService::class.java)
    }

    fun createLmsApi(sessionStore: SessionStore): LmsApiService {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BASIC
        }
        val authInterceptor = Interceptor { chain ->
            val request = chain.request()
            val bearer = sessionStore.bearerAuthorization()
            val next = if (bearer != null) {
                request.newBuilder().header("Authorization", bearer).build()
            } else {
                request
            }
            chain.proceed(next)
        }
        val client = OkHttpClient.Builder()
            .connectTimeout(20, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .addInterceptor(authInterceptor)
            .addInterceptor(logging)
            .build()

        val retrofit = Retrofit.Builder()
            .baseUrl(sessionStore.getApiBaseUrl())
            .client(client)
            .addConverterFactory(GsonConverterFactory.create(gson))
            .build()

        return retrofit.create(LmsApiService::class.java)
    }

    fun parseErrorMessage(response: retrofit2.Response<*>): String {
        val raw = response.errorBody()?.string().orEmpty()
        if (raw.isBlank()) {
            return when (response.code()) {
                401 -> "Invalid email or password."
                403 -> "Access denied. Your account cannot use the mobile portal."
                422 -> "Please check your input and try again."
                500, 502, 503 -> "Server error (${response.code()}). On your PC run: php artisan migrate"
                else -> "Request failed (${response.code()})."
            }
        }
        return try {
            val body = gson.fromJson(raw, ErrorBody::class.java)
            body.message?.takeIf { it.isNotBlank() }
                ?: body.errors?.values?.flatten()?.firstOrNull()
                ?: "Request failed (${response.code()})."
        } catch (_: Exception) {
            "Request failed (${response.code()})."
        }
    }
}
