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
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::middleware(['auth'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('role:admin,teacher')->name('dashboard');

    Route::get('/courses', [CourseController::class, 'index'])->middleware('role:admin,teacher')->name('courses.index');

    Route::get('/settings', [SettingsController::class, 'index'])->middleware('role:teacher')->name('settings');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->middleware('role:teacher')->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->middleware('role:teacher')->name('settings.password.update');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUsersController::class, 'store'])->name('users.store');
        Route::put('/users/{id}/password', [AdminUsersController::class, 'updatePassword'])->name('users.password.update');
        Route::delete('/users/{id}', [AdminUsersController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/restore', [AdminUsersController::class, 'restore'])->name('users.restore');

        Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings');
        Route::put('/settings/profile', [\App\Http\Controllers\Admin\SettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::put('/settings/password', [\App\Http\Controllers\Admin\SettingsController::class, 'updatePassword'])->name('settings.password.update');
    });

    Route::middleware('role:admin,teacher')->group(function (): void {
        Route::get('/sms-logs', [SmsLogController::class, 'index'])->name('sms-logs.index');
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
        Route::view('/mobile-app', 'mobile-app')->name('mobile.app');

        Route::get('/gradebook', [GradebookController::class, 'index'])->name('gradebook.index');
        Route::post('/gradebook', [GradebookController::class, 'store'])->name('gradebook.store');
        Route::get('/master-sheet', [MasterSheetController::class, 'index'])->name('master-sheet.index');
        Route::get('/subject-teacher', [SubjectTeacherController::class, 'index'])->name('subject-teacher.index');

        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

        Route::get('/report-cards', [ReportCardController::class, 'index'])->name('report-cards.index');
        Route::get('/report-cards/{enrollment}', [ReportCardController::class, 'show'])->name('report-cards.show');
        Route::post('/report-cards/{enrollment}/observed-values', [ReportCardController::class, 'updateObservedValues'])
            ->name('report-cards.observed-values.update');
    });
});
