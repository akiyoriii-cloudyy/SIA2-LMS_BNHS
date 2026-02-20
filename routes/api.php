<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MobileSyncController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth.api'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin,teacher')->group(function (): void {
        Route::get('/mobile/bootstrap', [MobileSyncController::class, 'bootstrap']);
        Route::get('/mobile/courses', [MobileSyncController::class, 'courses']);
        Route::get('/mobile/roster', [MobileSyncController::class, 'roster']);
        Route::get('/mobile/enrollments/{enrollment}/profile', [MobileSyncController::class, 'enrollmentProfile']);
        Route::post('/mobile/sync/attendance', [MobileSyncController::class, 'syncAttendance']);
    });
});
