<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\GradeEntry;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\SubjectFinalGrade;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterSheetImageSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = SchoolYear::query()->firstWhere('is_active', true)
            ?? SchoolYear::query()->first()
            ?? SchoolYear::query()->create([
                'name' => '2025-2026',
                'is_active' => true,
            ]);

        $section = Section::query()->updateOrCreate(
            ['name' => 'HUMSS', 'grade_level' => 11],
            ['track' => 'Academic', 'strand' => 'HUMSS']
        );

        $teacherUser = User::query()->first()
            ?? User::query()->create([
                'name' => 'Master Sheet Teacher',
                'email' => 'mastersheet-teacher@bnhs.local',
                'password' => bcrypt('password'),
            ]);

        $teacher = Teacher::query()->firstOrCreate(
            ['user_id' => $teacherUser->id],
            ['first_name' => 'Master', 'last_name' => 'Sheet']
        );

        $subjects = [
            ['code' => 'PR2', 'title' => 'Practical Research 2', 'category' => 'specialized'],
            ['code' => 'KOMPAN', 'title' => 'Komunikasyon at Pananaliksik', 'category' => 'core'],
            ['code' => 'CPAR', 'title' => 'Contemporary Philippine Arts in the Regions', 'category' => 'applied'],
            ['code' => 'FOP', 'title' => 'Food Processing', 'category' => 'specialized'],
            ['code' => 'PEH', 'title' => 'PE and Health', 'category' => 'core'],
            ['code' => 'INTROPH', 'title' => 'Intro to Philosophy', 'category' => 'core'],
            ['code' => 'ELS', 'title' => 'Earth and Life Science', 'category' => 'core'],
            ['code' => 'MIL', 'title' => 'Media and Information Literacy', 'category' => 'applied'],
        ];

        $subjectAssignments = [];
        foreach ($subjects as $subjectData) {
            $subject = Subject::query()->updateOrCreate(
                ['code' => $subjectData['code']],
                [
                    'title' => $subjectData['title'],
                    'category' => $subjectData['category'],
                ]
            );

            $assignment = SubjectAssignment::query()->firstOrCreate(
                [
                    'school_year_id' => $schoolYear->id,
                    'section_id' => $section->id,
                    'subject_id' => $subject->id,
                ],
                [
                    'teacher_id' => $teacher->id,
                ]
            );

            $subjectAssignments[] = $assignment;
        }

        $students = [
            ['first_name' => 'Daren', 'last_name' => 'Culzon', 'sex' => 'Male'],
            ['first_name' => 'Cablay', 'last_name' => 'Imaoi', 'sex' => 'Male'],
            ['first_name' => 'John Mark', 'last_name' => 'Mamalumpong', 'sex' => 'Male'],
            ['first_name' => 'John Eric', 'last_name' => 'Pontillo', 'sex' => 'Male'],
            ['first_name' => 'Albert', 'last_name' => 'Sudaria', 'sex' => 'Male'],
            ['first_name' => 'Deindree', 'last_name' => 'Torres', 'sex' => 'Male'],
            ['first_name' => 'Jeremy', 'last_name' => 'Sukin', 'sex' => 'Male'],
            ['first_name' => 'Joshua', 'last_name' => 'Sukin', 'sex' => 'Male'],
            ['first_name' => 'Mariel', 'last_name' => 'Ampang', 'sex' => 'Female'],
            ['first_name' => 'Aylee Nicole', 'last_name' => 'Antig', 'sex' => 'Female'],
            ['first_name' => 'Kathleen', 'last_name' => 'Bascon', 'sex' => 'Female'],
            ['first_name' => 'Reinae', 'last_name' => 'Cajato', 'sex' => 'Female'],
            ['first_name' => 'Jemma', 'last_name' => 'Flongcay', 'sex' => 'Female'],
            ['first_name' => 'Bea Jane', 'last_name' => 'Lucero', 'sex' => 'Female'],
            ['first_name' => 'Joy', 'last_name' => 'Magbanua', 'sex' => 'Female'],
            ['first_name' => 'Jeremae', 'last_name' => 'Peralta', 'sex' => 'Female'],
            ['first_name' => 'Princess Mae', 'last_name' => 'Rojas', 'sex' => 'Female'],
            ['first_name' => 'Jessa Mae', 'last_name' => 'Vargas', 'sex' => 'Female'],
        ];

        // Image-aligned baseline values per subject (Q1, Q2). Remaining rows are generated with stable offsets.
        $subjectQuarterBaseline = [
            [84.0, 80.0], // PR2
            [77.0, 82.0], // KOMPAN
            [90.0, 92.0], // CPAR
            [85.0, 80.0], // FOP
            [86.0, 86.0], // PEH
            [78.0, 80.0], // INTROPH
            [75.0, 80.0], // ELS
            [77.0, 80.0], // MIL
        ];

        $studentOffsets = [0, -2, -9, 0, 2, 0, 1, 1, 1, -7, 2, 12, -5, -5, -8, 6, -4, -4];
        $subjectWave = [0, 1, 2, 1, 0, -1, 0, 1];
        $quarter2Wave = [-1, 0, 1, 0, -1, 1, 0, 1];

        foreach ($students as $index => $studentData) {
            $student = Student::query()->updateOrCreate(
                [
                    'first_name' => $studentData['first_name'],
                    'last_name' => $studentData['last_name'],
                ],
                [
                    'lrn' => (string) (13131370001 + $index),
                    'sex' => $studentData['sex'],
                ]
            );

            $enrollment = Enrollment::query()->firstOrCreate(
                [
                    'student_id' => $student->id,
                    'school_year_id' => $schoolYear->id,
                ],
                [
                    'section_id' => $section->id,
                    'status' => 'active',
                ]
            );

            if ((int) $enrollment->section_id !== (int) $section->id) {
                $enrollment->update(['section_id' => $section->id, 'status' => 'active']);
            }

            foreach ($subjectAssignments as $subjectIndex => $assignment) {
                $baseQ1 = $subjectQuarterBaseline[$subjectIndex][0];
                $baseQ2 = $subjectQuarterBaseline[$subjectIndex][1];
                $offset = $studentOffsets[$index];

                $q1 = $this->clampScore($baseQ1 + $offset + $subjectWave[$subjectIndex]);
                $q2 = $this->clampScore($baseQ2 + $offset + $quarter2Wave[$subjectIndex]);
                $final = round(($q1 + $q2) / 2, 2);

                GradeEntry::query()->updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'subject_assignment_id' => $assignment->id,
                        'quarter' => 1,
                    ],
                    [
                        'quiz' => $q1,
                        'performance_task' => $q1,
                        'assignment' => $q1,
                        'exam' => $q1,
                        'quarter_grade' => $q1,
                    ]
                );

                GradeEntry::query()->updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'subject_assignment_id' => $assignment->id,
                        'quarter' => 2,
                    ],
                    [
                        'quiz' => $q2,
                        'performance_task' => $q2,
                        'assignment' => $q2,
                        'exam' => $q2,
                        'quarter_grade' => $q2,
                    ]
                );

                SubjectFinalGrade::query()->updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'subject_assignment_id' => $assignment->id,
                    ],
                    [
                        'q1' => $q1,
                        'q2' => $q2,
                        'q3' => null,
                        'q4' => null,
                        'final_grade' => $final,
                    ]
                );
            }
        }
    }

    private function clampScore(float $score): float
    {
        return round(max(75, min(98, $score)), 1);
    }
}

