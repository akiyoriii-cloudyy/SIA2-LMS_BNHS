<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MobileSyncController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api-login')->post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth.api'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::middleware('permission:lms.portal')->group(function (): void {
        Route::get('/mobile/bootstrap', [MobileSyncController::class, 'bootstrap']);
        Route::get('/mobile/courses', [MobileSyncController::class, 'courses']);
        Route::get('/mobile/roster', [MobileSyncController::class, 'roster']);
        Route::get('/mobile/enrollments/{enrollment}/profile', [MobileSyncController::class, 'enrollmentProfile']);
        Route::post('/mobile/rfid/scan', [MobileSyncController::class, 'rfidScan'])
            ->middleware('permission:attendance.manage');
        Route::post('/mobile/sync/attendance', [MobileSyncController::class, 'syncAttendance'])
            ->middleware('permission:attendance.manage');
    });
});
