<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GradingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::findRestoreOrCreate('admin', ['description' => 'School administrator']);
        $adminRole->update(['description' => 'School administrator']);
        $adviserRole = Role::findRestoreOrCreate('adviser', ['description' => 'Class adviser']);
        $adviserRole->update(['description' => 'Class adviser']);
        $subjectTeacherRole = Role::findRestoreOrCreate('subject_teacher', ['description' => 'Subject teacher']);
        $subjectTeacherRole->update(['description' => 'Subject teacher']);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@bnhs.local'],
            ['name' => 'School Admin', 'password' => Hash::make('password'), 'phone' => '+639111111111']
        );
        $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);

        $adviserUser = User::firstOrCreate(
            ['email' => 'adviser@bnhs.local'],
            ['name' => 'Adviser One', 'password' => Hash::make('password'), 'phone' => '+639222222222']
        );
        $adviserUser->roles()->syncWithoutDetaching([$adviserRole->id]);

        $adviser = Teacher::firstOrCreate(
            ['user_id' => $adviserUser->id],
            ['first_name' => 'Adviser', 'last_name' => 'One']
        );

        $schoolYear = SchoolYear::firstOrCreate(
            ['name' => '2025-2026'],
            ['is_active' => true]
        );

        $section = Section::updateOrCreate(
            ['name' => 'HUMSS', 'grade_level' => 11],
            ['track' => 'Academic', 'strand' => 'HUMSS']
        );

        $subjects = [
            ['code' => 'ORALCOMM', 'title' => 'Oral Communication in Context', 'category' => 'core'],
            ['code' => 'KOMPAN', 'title' => 'Komunikasyon at Pananaliksik', 'category' => 'core'],
            ['code' => '21CLIT', 'title' => '21st Century Literature', 'category' => 'core'],
            ['code' => 'CPAR', 'title' => 'Contemporary Philippine Arts', 'category' => 'applied'],
            ['code' => 'MIL', 'title' => 'Media and Information Literacy', 'category' => 'applied'],
            ['code' => 'PERDEV', 'title' => 'Personal Development', 'category' => 'applied'],
            ['code' => 'ELS', 'title' => 'Earth and Life Science', 'category' => 'core'],
            ['code' => 'PEH', 'title' => 'Physical Education and Health', 'category' => 'core'],
        ];

        $subjectTeachersByCode = [];
        foreach ($subjects as $subjectData) {
            $code = (string) $subjectData['code'];
            $email = strtolower($code).'.teacher@bnhs.local';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $code.' Teacher',
                    'password' => Hash::make('password'),
                    'phone' => '+63933'.str_pad((string) crc32($email) % 10000000, 7, '0', STR_PAD_LEFT),
                ]
            );
            $user->roles()->syncWithoutDetaching([$subjectTeacherRole->id]);

            $teacher = Teacher::firstOrCreate(
                ['user_id' => $user->id],
                ['first_name' => $code, 'last_name' => 'Teacher']
            );

            $subjectTeachersByCode[$code] = $teacher;
        }

        foreach ($subjects as $idx => $subjectData) {
            $subject = Subject::updateOrCreate(
                ['code' => $subjectData['code']],
                ['title' => $subjectData['title'], 'category' => $subjectData['category']]
            );
            $code = (string) $subject->code;
            $assignedTeacherId = $idx === 0
                ? $adviser->id
                : (int) ($subjectTeachersByCode[$code]?->id ?? $adviser->id);

            $assignment = SubjectAssignment::firstOrCreate([
                'school_year_id' => $schoolYear->id,
                'section_id' => $section->id,
                'subject_id' => $subject->id,
            ], [
                'teacher_id' => $assignedTeacherId,
            ]);

            if ((int) ($assignment->teacher_id ?? 0) !== $assignedTeacherId) {
                $assignment->update(['teacher_id' => $assignedTeacherId]);
            }

            Course::firstOrCreate([
                'school_year_id' => $schoolYear->id,
                'section_id' => $section->id,
                'subject_id' => $subject->id,
            ], [
                'teacher_id' => $adviser->id,
                'title' => $subject->title.' - Grade 11',
                'description' => 'LMS course for '.$subject->title,
                'is_published' => true,
            ]);
        }

        $students = [
            [
                'first_name' => 'Ana',
                'last_name' => 'Santos',
                'sex' => 'Female',
                'date_of_birth' => '2008-08-12',
                'address' => 'Purok 3, Bawing, General Santos City',
                'ethnicity' => 'Blaan',
            ],
            [
                'first_name' => 'Mark',
                'last_name' => 'Reyes',
                'sex' => 'Male',
                'date_of_birth' => '2008-03-21',
                'address' => 'Purok 5, Bawing, General Santos City',
                'ethnicity' => 'Islam',
            ],
            [
                'first_name' => 'Jessa',
                'last_name' => 'Cruz',
                'sex' => 'Female',
                'date_of_birth' => '2008-11-02',
                'address' => 'Purok 1, Bawing, General Santos City',
                'ethnicity' => 'Blaan',
            ],
        ];

        foreach ($students as $studentData) {
            $student = Student::updateOrCreate(
                ['first_name' => $studentData['first_name'], 'last_name' => $studentData['last_name']],
                [
                    'sex' => $studentData['sex'],
                    'date_of_birth' => $studentData['date_of_birth'],
                    'address' => $studentData['address'],
                    'ethnicity' => $studentData['ethnicity'],
                ]
            );

            $enrollment = Enrollment::firstOrCreate([
                'student_id' => $student->id,
                'school_year_id' => $schoolYear->id,
            ], [
                'section_id' => $section->id,
                'status' => 'active',
            ]);

            $guardian = Guardian::firstOrCreate(
                ['phone' => '+63933'.str_pad((string) $student->id, 7, '0', STR_PAD_LEFT)],
                [
                    'first_name' => 'Parent of '.$studentData['first_name'],
                    'last_name' => $studentData['last_name'],
                    'email' => strtolower($studentData['last_name']).'.guardian@bnhs.local',
                ]
            );

            $guardian->students()->syncWithoutDetaching([
                $student->id => [
                    'relationship' => 'Parent',
                    'is_primary' => true,
                    'receive_sms' => true,
                ],
            ]);
        }
    }
}
