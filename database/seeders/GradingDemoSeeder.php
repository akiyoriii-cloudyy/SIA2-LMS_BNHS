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
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'School administrator']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['description' => 'Subject teacher']);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@bnhs.local'],
            ['name' => 'School Admin', 'password' => Hash::make('password'), 'phone' => '+639111111111']
        );
        $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);

        $teacherUser = User::firstOrCreate(
            ['email' => 'teacher@bnhs.local'],
            ['name' => 'Adviser One', 'password' => Hash::make('password'), 'phone' => '+639222222222']
        );
        $teacherUser->roles()->syncWithoutDetaching([$teacherRole->id]);

        $teacher = Teacher::firstOrCreate(
            ['user_id' => $teacherUser->id],
            ['first_name' => 'Adviser', 'last_name' => 'One']
        );

        $schoolYear = SchoolYear::firstOrCreate(
            ['name' => '2025-2026'],
            ['is_active' => true]
        );

        $section = Section::updateOrCreate(
            ['name' => 'HUMSS Section A', 'grade_level' => 11],
            ['track' => 'Academic', 'strand' => 'HUMSS']
        );

        $subjects = [
            ['code' => 'ORALCOMM', 'title' => 'Oral Communication in Context'],
            ['code' => 'KOMPAN', 'title' => 'Komunikasyon at Pananaliksik'],
            ['code' => '21CLIT', 'title' => '21st Century Literature'],
            ['code' => 'CPAR', 'title' => 'Contemporary Philippine Arts'],
            ['code' => 'MIL', 'title' => 'Media and Information Literacy'],
            ['code' => 'PERDEV', 'title' => 'Personal Development'],
            ['code' => 'ELS', 'title' => 'Earth and Life Science'],
            ['code' => 'PEH', 'title' => 'Physical Education and Health'],
        ];

        foreach ($subjects as $subjectData) {
            $subject = Subject::firstOrCreate(['code' => $subjectData['code']], ['title' => $subjectData['title']]);
            SubjectAssignment::firstOrCreate([
                'school_year_id' => $schoolYear->id,
                'section_id' => $section->id,
                'subject_id' => $subject->id,
            ], [
                'teacher_id' => $teacher->id,
            ]);

            Course::firstOrCreate([
                'school_year_id' => $schoolYear->id,
                'section_id' => $section->id,
                'subject_id' => $subject->id,
            ], [
                'teacher_id' => $teacher->id,
                'title' => $subject->title.' - Grade 11',
                'description' => 'LMS course for '.$subject->title,
                'is_published' => true,
            ]);
        }

        $students = [
            ['first_name' => 'Ana', 'last_name' => 'Santos', 'sex' => 'Female'],
            ['first_name' => 'Mark', 'last_name' => 'Reyes', 'sex' => 'Male'],
            ['first_name' => 'Jessa', 'last_name' => 'Cruz', 'sex' => 'Female'],
        ];

        foreach ($students as $studentData) {
            $student = Student::firstOrCreate(
                ['first_name' => $studentData['first_name'], 'last_name' => $studentData['last_name']],
                ['sex' => $studentData['sex']]
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
