<?php

/**
 * Script to delete old individual subject teachers after consolidation
 * Keeps only: Subject Teacher 1, Subject Teacher 2, and Adviser
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Cleaning Up Old Subject Teachers ===\n\n";

// Emails to KEEP (the consolidated ones)
$emailsToKeep = [
    'subject.teacher1@bnhs.local',
    'subject.teacher2@bnhs.local',
    'adviser@bnhs.local',
    'admin@bnhs.local', // Keep admin too
];

// Find old subject teacher emails to delete
$oldSubjectTeachers = User::query()
    ->whereHas('roles', fn($q) => $q->where('name', 'subject_teacher'))
    ->whereNotIn('email', $emailsToKeep)
    ->get();

echo "Found " . $oldSubjectTeachers->count() . " old subject teachers to remove:\n";

foreach ($oldSubjectTeachers as $user) {
    echo "  - {$user->name} ({$user->email})\n";
}

if ($oldSubjectTeachers->isEmpty()) {
    echo "  (No old teachers found - already clean!)\n";
}

echo "\n";

// Delete old teachers and their assignments
$deletedCount = 0;
foreach ($oldSubjectTeachers as $user) {
    $teacher = Teacher::query()->where('user_id', $user->id)->first();
    
    if ($teacher) {
        // Delete assignments first
        SubjectAssignment::query()->where('teacher_id', $teacher->id)->delete();
        TeacherSubject::query()->where('teacher_id', $teacher->id)->delete();
        
        // Delete teacher profile
        $teacher->delete();
    }
    
    // Delete user
    $user->roles()->detach(); // Remove role assignments
    $user->delete();
    
    echo "  ✅ Deleted: {$user->name}\n";
    $deletedCount++;
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║           CLEANUP COMPLETE ✅                              ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";
echo "║                                                            ║\n";
echo "║  Deleted: {$deletedCount} old subject teachers                         ║\n";
echo "║                                                            ║\n";
echo "║  Remaining users with JWT/MFA access:                      ║\n";
echo "║  • Admin (admin@bnhs.local)                                 ║\n";
echo "║  • Adviser (adviser@bnhs.local)                           ║\n";
echo "║  • Subject Teacher 1 (subject.teacher1@bnhs.local)          ║\n";
echo "║  • Subject Teacher 2 (subject.teacher2@bnhs.local)            ║\n";
echo "║                                                            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

echo "\n📝 Refresh the User Management page to see the updated list.\n";
