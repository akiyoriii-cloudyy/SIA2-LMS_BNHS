<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\GradeEntry;
use App\Models\SubjectAssignment;
use App\Models\SubjectFinalGrade;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterSheetRandomGradesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $enrollments = Enrollment::query()->get(['id', 'section_id', 'school_year_id']);

            foreach ($enrollments as $enrollment) {
                $assignments = SubjectAssignment::query()
                    ->where('school_year_id', $enrollment->school_year_id)
                    ->where('section_id', $enrollment->section_id)
                    ->get(['id']);

                foreach ($assignments as $assignment) {
                    $q1 = $this->upsertQuarterGrade($enrollment->id, $assignment->id, 1);
                    $q2 = $this->upsertQuarterGrade($enrollment->id, $assignment->id, 2);
                    $q3 = $this->upsertQuarterGrade($enrollment->id, $assignment->id, 3);
                    $q4 = $this->upsertQuarterGrade($enrollment->id, $assignment->id, 4);

                    $subjectFinal = SubjectFinalGrade::query()->firstOrNew([
                        'enrollment_id' => $enrollment->id,
                        'subject_assignment_id' => $assignment->id,
                    ]);

                    $subjectFinal->q1 = $q1;
                    $subjectFinal->q2 = $q2;
                    $subjectFinal->q3 = $q3;
                    $subjectFinal->q4 = $q4;
                    $subjectFinal->final_grade = round(($q1 + $q2 + $q3 + $q4) / 4, 2);

                    $subjectFinal->save();
                }
            }
        });
    }

    private function upsertQuarterGrade(int $enrollmentId, int $subjectAssignmentId, int $quarter): float
    {
        $quiz = $this->randomScore();
        $performanceTask = $this->randomScore();
        $exam = $this->randomScore();

        $quarterGrade = round(($quiz * 0.30) + ($performanceTask * 0.30) + ($exam * 0.40), 2);

        GradeEntry::query()->updateOrCreate(
            [
                'enrollment_id' => $enrollmentId,
                'subject_assignment_id' => $subjectAssignmentId,
                'quarter' => $quarter,
            ],
            [
                'quiz' => $quiz,
                'performance_task' => $performanceTask,
                // Keep legacy assignment column in sync.
                'assignment' => $performanceTask,
                'exam' => $exam,
                'quarter_grade' => $quarterGrade,
            ]
        );

        return $quarterGrade;
    }

    private function randomScore(): float
    {
        // 80.0 to 98.0 in 0.5 steps for realistic passing grades.
        return random_int(160, 196) / 2;
    }
}
