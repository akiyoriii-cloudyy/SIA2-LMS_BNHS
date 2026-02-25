<?php

use App\Http\Controllers\GradebookController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
<<<<<<< HEAD
=======
use App\Http\Controllers\Admin\UsersController as AdminUsersController;
>>>>>>> f3df034 (Update the Admin Dashboard)
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SmsLogController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware(['auth'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/courses', [CourseController::class, 'index'])->middleware('role:admin,teacher,student')->name('courses.index');

<<<<<<< HEAD
=======
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

>>>>>>> f3df034 (Update the Admin Dashboard)
    Route::middleware('role:admin,teacher')->group(function (): void {
        Route::get('/system/tables', [DashboardController::class, 'systemTables'])->name('system.tables');
        Route::get('/sms-logs', [SmsLogController::class, 'index'])->name('sms-logs.index');
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::view('/mobile-app', 'mobile-app')->name('mobile.app');

        Route::get('/gradebook', [GradebookController::class, 'index'])->name('gradebook.index');
        Route::post('/gradebook', [GradebookController::class, 'store'])->name('gradebook.store');

        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

        Route::get('/report-cards', [ReportCardController::class, 'index'])->name('report-cards.index');
        Route::get('/report-cards/{enrollment}', [ReportCardController::class, 'show'])->name('report-cards.show');
    });
});
