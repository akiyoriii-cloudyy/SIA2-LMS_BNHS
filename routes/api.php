<?php

use App\Http\Controllers\Api\AttendanceMonthlyReportController as ApiAttendanceMonthlyReportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MobileProfileController;
use App\Http\Controllers\Api\MobileRecordsController;
use App\Http\Controllers\Api\MobileSyncController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api-login')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
});

Route::middleware(['auth.api'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/rbac', [AuthController::class, 'rbacProfile']);

    Route::middleware('permission:lms.portal')->group(function (): void {
        Route::get('/mobile/profile', [MobileProfileController::class, 'show']);
        Route::put('/mobile/profile', [MobileProfileController::class, 'update']);

        Route::get('/mobile/bootstrap', [MobileSyncController::class, 'bootstrap']);
        Route::get('/mobile/courses', [MobileSyncController::class, 'courses']);
        Route::get('/mobile/roster', [MobileSyncController::class, 'roster']);
        Route::get('/mobile/enrollments/{enrollment}/profile', [MobileSyncController::class, 'enrollmentProfile']);
        Route::post('/mobile/rfid/scan', [MobileSyncController::class, 'rfidScan'])
            ->middleware('permission:attendance.manage');
        Route::post('/mobile/sync/attendance', [MobileSyncController::class, 'syncAttendance'])
            ->middleware('permission:attendance.manage');

        Route::middleware('permission:attendance.manage')->prefix('mobile/attendance')->group(function (): void {
            Route::get('/monthly-reports', [ApiAttendanceMonthlyReportController::class, 'index']);
            Route::get('/monthly-reports/{attendanceMonthlyReport}', [ApiAttendanceMonthlyReportController::class, 'show']);
        });

        Route::middleware('permission:records.manage')->prefix('mobile/records')->group(function (): void {
            Route::get('/students', [MobileRecordsController::class, 'index']);
            Route::post('/students', [MobileRecordsController::class, 'store']);
            Route::put('/students/{student}', [MobileRecordsController::class, 'update']);
            Route::delete('/students/{student}', [MobileRecordsController::class, 'destroy']);
        });
    });
});
