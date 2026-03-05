<?php

use App\Http\Controllers\GradebookController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;

use App\Http\Controllers\Admin\UsersController as AdminUsersController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterSheetController;
use App\Http\Controllers\SmsLogController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectTeacherController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.submit');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::middleware(['auth'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['role:admin,teacher', 'permission:dashboard.view'])->name('dashboard');

    Route::get('/courses', [CourseController::class, 'index'])->middleware(['role:admin,teacher', 'permission:courses.view'])->name('courses.index');

    Route::get('/settings', [SettingsController::class, 'index'])->middleware(['role:teacher', 'permission:settings.manage_own'])->name('settings');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->middleware(['role:teacher', 'permission:settings.manage_own'])->name('settings.profile.update');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::middleware('permission:users.manage')->group(function (): void {
            Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
            Route::post('/users', [AdminUsersController::class, 'store'])->name('users.store');
            Route::put('/users/{id}/password', [AdminUsersController::class, 'updatePassword'])->name('users.password.update');
            Route::delete('/users/{id}', [AdminUsersController::class, 'destroy'])->name('users.destroy');
            Route::post('/users/{id}/restore', [AdminUsersController::class, 'restore'])->name('users.restore');
        });

        Route::middleware('permission:settings.manage')->group(function (): void {
            Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings');
            Route::put('/settings/profile', [\App\Http\Controllers\Admin\SettingsController::class, 'updateProfile'])->name('settings.profile.update');
            Route::put('/settings/password', [\App\Http\Controllers\Admin\SettingsController::class, 'updatePassword'])->name('settings.password.update');
        });
    });

    Route::middleware('role:admin,teacher')->group(function (): void {
        Route::get('/sms-logs', [SmsLogController::class, 'index'])->middleware('permission:sms_logs.view')->name('sms-logs.index');

        Route::middleware('permission:records.manage')->group(function (): void {
            Route::get('/students', [StudentController::class, 'index'])->name('students.index');
            Route::post('/students', [StudentController::class, 'store'])->name('students.store');
            Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
            Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
            Route::post('/students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');
            Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
            Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
            Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
            Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
            Route::post('/subjects/{id}/restore', [SubjectController::class, 'restore'])->name('subjects.restore');
        });
        Route::view('/mobile-app', 'mobile-app')->name('mobile.app');

        Route::get('/gradebook', [GradebookController::class, 'index'])->middleware('permission:gradebook.view')->name('gradebook.index');
        Route::post('/gradebook', [GradebookController::class, 'store'])->middleware('permission:gradebook.edit')->name('gradebook.store');
        Route::get('/master-sheet', [MasterSheetController::class, 'index'])->name('master-sheet.index');
        Route::get('/subject-teacher', [SubjectTeacherController::class, 'index'])->name('subject-teacher.index');

        Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('permission:attendance.manage')->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->middleware('permission:attendance.manage')->name('attendance.store');

        Route::get('/report-cards', [ReportCardController::class, 'index'])->middleware('permission:report_cards.view')->name('report-cards.index');
        Route::get('/report-cards/{enrollment}', [ReportCardController::class, 'show'])->middleware('permission:report_cards.view')->name('report-cards.show');
        Route::post('/report-cards/{enrollment}/observed-values', [ReportCardController::class, 'updateObservedValues'])
            ->middleware('permission:report_cards.edit')
            ->name('report-cards.observed-values.update');
    });
});
