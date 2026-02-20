<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\GradeEntry;
use App\Models\ReportCard;
use App\Models\ReportCardItem;
use App\Models\SubjectAssignment;
use App\Models\SubjectFinalGrade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class GradingService
{
    private const QUIZ_WEIGHT = 0.30;
    private const ASSIGNMENT_WEIGHT = 0.30;
    private const EXAM_WEIGHT = 0.40;

    public function upsertQuarterGrade(
        Enrollment $enrollment,
        SubjectAssignment $assignment,
        int $quarter,
        ?float $quiz,
        ?float $assignmentScore,
        ?float $exam
    ): void {
        DB::transaction(function () use ($enrollment, $assignment, $quarter, $quiz, $assignmentScore, $exam): void {
            $quarterGrade = $this->computeQuarterGrade($quiz, $assignmentScore, $exam);

            GradeEntry::updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'subject_assignment_id' => $assignment->id,
                    'quarter' => $quarter,
                ],
                [
                    'quiz' => $quiz,
                    'assignment' => $assignmentScore,
                    'exam' => $exam,
                    'quarter_grade' => $quarterGrade,
                ]
            );

            $this->syncSubjectFinalGrade($enrollment, $assignment);
            $this->syncEnrollmentReportCard($enrollment);
        });
    }

    public function syncEnrollmentReportCard(Enrollment $enrollment): void
    {
        $reportCard = ReportCard::firstOrCreate(
            ['enrollment_id' => $enrollment->id],
            ['general_average' => null]
        );

        $assignmentIds = SubjectAssignment::query()
            ->where('section_id', $enrollment->section_id)
            ->where('school_year_id', $enrollment->school_year_id)
            ->pluck('id');

        $subjectFinalGrades = SubjectFinalGrade::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('subject_assignment_id', $assignmentIds)
            ->get()
            ->keyBy('subject_assignment_id');

        $now = Carbon::now();
        $rows = [];
        foreach ($assignmentIds as $assignmentId) {
            $grade = $subjectFinalGrades->get($assignmentId);
            $rows[] = [
                'report_card_id' => $reportCard->id,
                'subject_assignment_id' => $assignmentId,
                'q1' => $grade?->q1,
                'q2' => $grade?->q2,
                'q3' => $grade?->q3,
                'q4' => $grade?->q4,
                'final_grade' => $grade?->final_grade,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            ReportCardItem::upsert(
                $rows,
                ['report_card_id', 'subject_assignment_id'],
                ['q1', 'q2', 'q3', 'q4', 'final_grade', 'updated_at']
            );
        }

        $staleItemsQuery = ReportCardItem::query()->where('report_card_id', $reportCard->id);
        if ($assignmentIds->isNotEmpty()) {
            $staleItemsQuery->whereNotIn('subject_assignment_id', $assignmentIds);
        }
        $staleItemsQuery->delete();

        $totalSubjects = $assignmentIds->count();
        $completedSubjects = $subjectFinalGrades->whereNotNull('final_grade')->count();

        $reportCard->update([
            'general_average' => $totalSubjects > 0 && $completedSubjects === $totalSubjects
                ? round((float) $subjectFinalGrades->avg('final_grade'), 2)
                : null,
        ]);
    }

    private function syncSubjectFinalGrade(Enrollment $enrollment, SubjectAssignment $assignment): void
    {
        $quarterGrades = GradeEntry::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('subject_assignment_id', $assignment->id)
            ->pluck('quarter_grade', 'quarter');

        $q1 = $quarterGrades->get(1);
        $q2 = $quarterGrades->get(2);
        $q3 = $quarterGrades->get(3);
        $q4 = $quarterGrades->get(4);

        SubjectFinalGrade::updateOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'subject_assignment_id' => $assignment->id,
            ],
            [
                'q1' => $q1,
                'q2' => $q2,
                'q3' => $q3,
                'q4' => $q4,
                'final_grade' => $this->computeFinalGrade($q1, $q2, $q3, $q4),
            ]
        );
    }

    private function computeQuarterGrade(?float $quiz, ?float $assignment, ?float $exam): ?float
    {
        if ($quiz === null || $assignment === null || $exam === null) {
            return null;
        }

        return round(
            ($quiz * self::QUIZ_WEIGHT) + ($assignment * self::ASSIGNMENT_WEIGHT) + ($exam * self::EXAM_WEIGHT),
            2
        );
    }

    private function computeFinalGrade(?float $q1, ?float $q2, ?float $q3, ?float $q4): ?float
    {
        if ($q1 === null || $q2 === null || $q3 === null || $q4 === null) {
            return null;
        }

        return round(($q1 + $q2 + $q3 + $q4) / 4, 2);
    }
}
