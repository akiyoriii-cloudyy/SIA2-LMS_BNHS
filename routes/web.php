<?php

use App\Http\Controllers\GradebookController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MfaController;
use App\Http\Controllers\PasswordResetController;

use App\Http\Controllers\Admin\UsersController as AdminUsersController;
use App\Http\Controllers\Admin\SystemManagementController as AdminSystemManagementController;
use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterSheetController;
use App\Http\Controllers\SmsLogController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectTeacherController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.submit');
    Route::get('/mfa/challenge', [MfaController::class, 'challenge'])->name('mfa.challenge');
    Route::post('/mfa/challenge', [MfaController::class, 'verify'])->middleware('throttle:login')->name('mfa.verify');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::middleware(['auth'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['role:admin,adviser,subject_teacher', 'permission:dashboard.view'])->name('dashboard');

    Route::middleware(['role:admin,adviser,subject_teacher', 'permission:dashboard.view'])->group(function (): void {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{schoolNotification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    });

    Route::get('/courses', [CourseController::class, 'index'])->middleware(['role:adviser', 'permission:courses.view'])->name('courses.index');

    Route::get('/settings', [SettingsController::class, 'index'])->middleware(['role:admin,adviser,subject_teacher', 'permission:settings.manage_own,settings.manage'])->name('settings');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->middleware(['role:admin,adviser,subject_teacher', 'permission:settings.manage_own,settings.manage'])->name('settings.profile.update');
    Route::get('/settings/mfa', [MfaController::class, 'setup'])->middleware(['role:admin,adviser,subject_teacher', 'permission:settings.manage_own,settings.manage'])->name('settings.mfa');
    Route::post('/settings/mfa/enable', [MfaController::class, 'enable'])->middleware(['role:admin,adviser,subject_teacher', 'permission:settings.manage_own,settings.manage'])->name('settings.mfa.enable');
    Route::post('/settings/mfa/disable', [MfaController::class, 'disable'])->middleware(['role:admin,adviser,subject_teacher', 'permission:settings.manage_own,settings.manage'])->name('settings.mfa.disable');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::middleware('permission:users.manage')->group(function (): void {
            Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
            Route::post('/users', [AdminUsersController::class, 'store'])->name('users.store');
            Route::put('/users/{id}', [AdminUsersController::class, 'update'])->name('users.update');
            Route::put('/users/{id}/password', [AdminUsersController::class, 'updatePassword'])->name('users.password.update');
            Route::delete('/users/{id}', [AdminUsersController::class, 'destroy'])->name('users.destroy');
            Route::post('/users/{id}/restore', [AdminUsersController::class, 'restore'])->name('users.restore');
        });

        Route::middleware('permission:settings.manage')->group(function (): void {
            Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings');
            Route::put('/settings/profile', [\App\Http\Controllers\Admin\SettingsController::class, 'updateProfile'])->name('settings.profile.update');
            Route::put('/settings/password', [\App\Http\Controllers\Admin\SettingsController::class, 'updatePassword'])->name('settings.password.update');

            Route::get('/system-management', [AdminSystemManagementController::class, 'index'])->name('system.index');
            Route::get('/system-management/academic-settings', [AdminSystemManagementController::class, 'academicSettings'])->name('system.academic-settings');
            Route::post('/system-management/academic-years', [AdminSystemManagementController::class, 'storeAcademicYear'])->name('system.academic-years.store');
            Route::post('/system-management/academic-years/{schoolYear}/set-current', [AdminSystemManagementController::class, 'setCurrentAcademicYear'])->name('system.academic-years.set-current');
            Route::post('/system-management/semesters', [AdminSystemManagementController::class, 'storeSemester'])->name('system.semesters.store');
            Route::post('/system-management/semesters/{semester}/set-current', [AdminSystemManagementController::class, 'setCurrentSemester'])->name('system.semesters.set-current');

            Route::get('/system-management/strands', [AdminSystemManagementController::class, 'strands'])->name('system.strands');
            Route::post('/system-management/strands', [AdminSystemManagementController::class, 'storeStrand'])->name('system.strands.store');
            Route::put('/system-management/strands/{strand}', [AdminSystemManagementController::class, 'updateStrand'])->name('system.strands.update');
            Route::delete('/system-management/strands/{strand}', [AdminSystemManagementController::class, 'destroyStrand'])->name('system.strands.destroy');

            Route::get('/system-management/terms', [AdminSystemManagementController::class, 'terms'])->name('system.terms');
            Route::post('/system-management/terms', [AdminSystemManagementController::class, 'storeTerm'])->name('system.terms.store');
            Route::put('/system-management/terms/{term}', [AdminSystemManagementController::class, 'updateTerm'])->name('system.terms.update');
            Route::delete('/system-management/terms/{term}', [AdminSystemManagementController::class, 'destroyTerm'])->name('system.terms.destroy');

            Route::get('/system-management/subjects', [AdminSystemManagementController::class, 'subjects'])->name('system.subjects');
            Route::post('/system-management/subjects', [AdminSystemManagementController::class, 'storeSubject'])->name('system.subjects.store');
            Route::put('/system-management/subjects/{subject}', [AdminSystemManagementController::class, 'updateSubject'])->name('system.subjects.update');
            Route::post('/system-management/subjects/assign-teacher', [AdminSystemManagementController::class, 'assignTeacherSubject'])->name('system.subjects.assign-teacher');
        });
    });

    Route::middleware(['role:admin,adviser,subject_teacher', 'permission:activity_logs.view'])->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/activity-logs/stats', [AdminActivityLogController::class, 'stats'])->name('activity-logs.stats');
        Route::get('/activity-logs/export', [AdminActivityLogController::class, 'export'])->name('activity-logs.export');
        Route::get('/activity-logs/{activityLog}', [AdminActivityLogController::class, 'show'])->name('activity-logs.show');
        Route::put('/activity-logs/{activityLog}/notes', [AdminActivityLogController::class, 'updateNotes'])->name('activity-logs.notes.update');
        Route::put('/activity-logs/{activityLog}/custom-action', [AdminActivityLogController::class, 'updateCustomAction'])->name('activity-logs.custom-action.update');
        Route::get('/users/{userId}/sessions', [AdminActivityLogController::class, 'userSessions'])->name('activity-logs.user-sessions');

        Route::middleware(['role:admin', 'permission:activity_logs.manage'])->group(function (): void {
            Route::get('/sessions/active', [AdminActivityLogController::class, 'activeSessions'])->name('sessions.active');
            Route::delete('/sessions/{sessionId}/terminate', [AdminActivityLogController::class, 'terminateSession'])->name('sessions.terminate');
            Route::delete('/activity-logs/{activityLog}', [AdminActivityLogController::class, 'destroy'])->name('activity-logs.destroy');
            Route::post('/activity-logs/bulk-delete', [AdminActivityLogController::class, 'bulkDelete'])->name('activity-logs.bulk-delete');
        });
    });

    Route::middleware(['role:adviser,subject_teacher'])->group(function (): void {
        Route::get('/gradebook', [GradebookController::class, 'index'])->middleware('permission:gradebook.view')->name('gradebook.index');
        Route::post('/gradebook', [GradebookController::class, 'store'])->middleware('permission:gradebook.edit')->name('gradebook.store');
    });

    Route::middleware(['role:admin,adviser', 'permission:sms_logs.view'])->group(function (): void {
        Route::get('/sms-logs', [SmsLogController::class, 'index'])->name('sms-logs.index');
    });

    Route::middleware(['role:admin,adviser'])->group(function (): void {
        Route::view('/mobile-app', 'mobile-app')->name('mobile.app');
    });

    Route::middleware('role:adviser,subject_teacher')->group(function (): void {
        Route::get('/subject-teacher', [SubjectTeacherController::class, 'index'])->name('subject-teacher.index');
    });

    Route::middleware('role:adviser')->group(function (): void {
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

        Route::get('/master-sheet', [MasterSheetController::class, 'index'])->name('master-sheet.index');

        Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('permission:attendance.manage')->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->middleware('permission:attendance.manage')->name('attendance.store');

        Route::get('/report-cards', [ReportCardController::class, 'index'])->middleware('permission:report_cards.view')->name('report-cards.index');
        Route::get('/report-cards/{enrollment}', [ReportCardController::class, 'show'])->middleware('permission:report_cards.view')->name('report-cards.show');
        Route::post('/report-cards/{enrollment}/observed-values', [ReportCardController::class, 'updateObservedValues'])
            ->middleware('permission:report_cards.edit')
            ->name('report-cards.observed-values.update');
    });
});
