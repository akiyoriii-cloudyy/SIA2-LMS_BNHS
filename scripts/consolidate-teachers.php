<?php

/**
 * Script to consolidate multiple subject teachers into 2 subject teachers + 1 adviser
 * Subject Teacher 1: 4 subjects
 * Subject Teacher 2: 3 subjects  
 * Adviser: 1 subject
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Consolidating Teachers for JWT/MFA Testing ===\n\n";

// Get current active school year
$schoolYear = SchoolYear::query()->where('is_active', true)->first()
    ?? SchoolYear::query()->orderByDesc('name')->first();

if (! $schoolYear) {
    echo "❌ No school year found. Please run seeders first.\n";
    exit(1);
}

echo "📅 School Year: {$schoolYear->name}\n";

// Get all 8 subjects
$subjects = Subject::query()->whereIn('code', [
    'ORALCOMM', 'KOMPAN', '21CLIT', 'CPAR', 
    'MIL', 'PERDEV', 'ELS', 'PEH'
])->get()->keyBy('code');

echo "📚 Found " . $subjects->count() . " subjects\n";

if ($subjects->count() < 8) {
    echo "⚠️ Expected 8 subjects but found {$subjects->count()}\n";
}

// Create or update 2 consolidated subject teachers
$teacher1Subjects = ['ORALCOMM', 'KOMPAN', '21CLIT', 'CPAR']; // 4 subjects
$teacher2Subjects = ['MIL', 'PERDEV', 'ELS']; // 3 subjects
$adviserSubjects = ['PEH']; // 1 subject

$subjectTeacherRole = Role::query()->where('name', 'subject_teacher')->first();
$adviserRole = Role::query()->where('name', 'adviser')->first();

if (! $subjectTeacherRole || ! $adviserRole) {
    echo "❌ Roles not found. Please run RBAC seeder.\n";
    exit(1);
}

// Subject Teacher 1 (4 subjects)
echo "\n👨‍🏫 Creating Subject Teacher 1...\n";
$user1 = User::firstOrCreate(
    ['email' => 'subject.teacher1@bnhs.local'],
    [
        'name' => 'Subject Teacher One',
        'password' => Hash::make('password123'),
        'email_verified_at' => now(),
    ]
);
$user1->roles()->sync([$subjectTeacherRole->id]);
$teacher1 = Teacher::firstOrCreate(
    ['user_id' => $user1->id],
    ['first_name' => 'Subject', 'last_name' => 'Teacher One']
);
echo "   ✅ {$user1->name} ({$user1->email}) - Password: password123\n";

// Subject Teacher 2 (3 subjects)
echo "\n👨‍🏫 Creating Subject Teacher 2...\n";
$user2 = User::firstOrCreate(
    ['email' => 'subject.teacher2@bnhs.local'],
    [
        'name' => 'Subject Teacher Two',
        'password' => Hash::make('password123'),
        'email_verified_at' => now(),
    ]
);
$user2->roles()->sync([$subjectTeacherRole->id]);
$teacher2 = Teacher::firstOrCreate(
    ['user_id' => $user2->id],
    ['first_name' => 'Subject', 'last_name' => 'Teacher Two']
);
echo "   ✅ {$user2->name} ({$user2->email}) - Password: password123\n";

// Get or create Adviser (1 subject)
echo "\n👨‍🏫 Setting up Adviser...\n";
$adviserUser = User::query()->whereHas('roles', fn($q) => $q->where('name', 'adviser'))->first();

if (! $adviserUser) {
    $adviserUser = User::firstOrCreate(
        ['email' => 'adviser@bnhs.local'],
        [
            'name' => 'Adviser One',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]
    );
    $adviserUser->roles()->sync([$adviserRole->id]);
}

$adviser = Teacher::firstOrCreate(
    ['user_id' => $adviserUser->id],
    ['first_name' => 'Adviser', 'last_name' => 'One']
);
echo "   ✅ {$adviserUser->name} ({$adviserUser->email}) - Password: password123\n";

// Get first section for assignments
$section = Section::query()->first();
if (! $section) {
    echo "❌ No sections found. Please run seeders first.\n";
    exit(1);
}

// Clear existing assignments for these subjects to avoid conflicts
echo "\n🧹 Clearing existing subject assignments...\n";
SubjectAssignment::query()
    ->whereIn('subject_id', $subjects->pluck('id'))
    ->where('school_year_id', $schoolYear->id)
    ->delete();

TeacherSubject::query()
    ->whereIn('subject_id', $subjects->pluck('id'))
    ->where('school_year_id', $schoolYear->id)
    ->delete();

// Assign subjects to Teacher 1 (4 subjects)
echo "\n📋 Assigning subjects to Teacher 1 (4 subjects):\n";
foreach ($teacher1Subjects as $code) {
    if (! $subjects->has($code)) {
        echo "   ⚠️ Subject $code not found\n";
        continue;
    }
    $subject = $subjects[$code];
    
    SubjectAssignment::create([
        'teacher_id' => $teacher1->id,
        'section_id' => $section->id,
        'subject_id' => $subject->id,
        'school_year_id' => $schoolYear->id,
    ]);
    
    TeacherSubject::create([
        'teacher_id' => $teacher1->id,
        'subject_id' => $subject->id,
        'school_year_id' => $schoolYear->id,
        'section_id' => $section->id,
        'is_active' => true,
    ]);
    
    echo "   ✅ {$subject->code} - {$subject->title}\n";
}

// Assign subjects to Teacher 2 (3 subjects)
echo "\n📋 Assigning subjects to Teacher 2 (3 subjects):\n";
foreach ($teacher2Subjects as $code) {
    if (! $subjects->has($code)) {
        echo "   ⚠️ Subject $code not found\n";
        continue;
    }
    $subject = $subjects[$code];
    
    SubjectAssignment::create([
        'teacher_id' => $teacher2->id,
        'section_id' => $section->id,
        'subject_id' => $subject->id,
        'school_year_id' => $schoolYear->id,
    ]);
    
    TeacherSubject::create([
        'teacher_id' => $teacher2->id,
        'subject_id' => $subject->id,
        'school_year_id' => $schoolYear->id,
        'section_id' => $section->id,
        'is_active' => true,
    ]);
    
    echo "   ✅ {$subject->code} - {$subject->title}\n";
}

// Assign subject to Adviser (1 subject)
echo "\n📋 Assigning subject to Adviser (1 subject):\n";
foreach ($adviserSubjects as $code) {
    if (! $subjects->has($code)) {
        echo "   ⚠️ Subject $code not found\n";
        continue;
    }
    $subject = $subjects[$code];
    
    SubjectAssignment::create([
        'teacher_id' => $adviser->id,
        'section_id' => $section->id,
        'subject_id' => $subject->id,
        'school_year_id' => $schoolYear->id,
    ]);
    
    TeacherSubject::create([
        'teacher_id' => $adviser->id,
        'subject_id' => $subject->id,
        'school_year_id' => $schoolYear->id,
        'section_id' => $section->id,
        'is_active' => true,
    ]);
    
    echo "   ✅ {$subject->code} - {$subject->title}\n";
}

// Summary
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║           CONSOLIDATION COMPLETE ✅                        ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";
echo "║                                                            ║\n";
echo "║  📧 Account 1 (4 subjects):                                ║\n";
echo "║     Email: subject.teacher1@bnhs.local                     ║\n";
echo "║     Pass:  password123                                     ║\n";
echo "║     Role:  subject_teacher                                 ║\n";
echo "║     Subjects: ORALCOMM, KOMPAN, 21CLIT, CPAR              ║\n";
echo "║                                                            ║\n";
echo "║  📧 Account 2 (3 subjects):                                ║\n";
echo "║     Email: subject.teacher2@bnhs.local                     ║\n";
echo "║     Pass:  password123                                     ║\n";
echo "║     Role:  subject_teacher                                 ║\n";
echo "║     Subjects: MIL, PERDEV, ELS                            ║\n";
echo "║                                                            ║\n";
echo "║  📧 Account 3 (1 subject + Adviser):                     ║\n";
echo "║     Email: {$adviserUser->email}                            ║\n";
echo "║     Pass:  password123                                     ║\n";
echo "║     Role:  adviser                                         ║\n";
echo "║     Subject: PEH                                          ║\n";
echo "║                                                            ║\n";
echo "║  ALL 3 ACCOUNTS CAN USE:                                   ║\n";
echo "║  • JWT Token API (login/logout/revocation)                ║\n";
echo "║  • MFA (enable in Settings → Manage MFA)                    ║\n";
echo "║                                                            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

echo "\n📝 JWT Testing Commands:\n";
echo "```powershell\n";
echo "\$base = \"http://localhost/LMS_BNHS/api\"\n";
echo "\$loginBody = @{ email=\"subject.teacher1@bnhs.local\"; password=\"password123\" } | ConvertTo-Json\n";
echo "\$res = Invoke-RestMethod -Method Post -Uri \"\$base/auth/login\" -ContentType \"application/json\" -Body \$loginBody\n";
echo "\$token = \$res.token\n";
echo "\$headers = @{ Authorization = \"Bearer \$token\" }\n";
echo "Invoke-RestMethod -Uri \"\$base/mobile/courses\" -Headers \$headers\n";
echo "Invoke-RestMethod -Method Post -Uri \"\$base/auth/logout\" -Headers \$headers\n";
echo "```\n";
