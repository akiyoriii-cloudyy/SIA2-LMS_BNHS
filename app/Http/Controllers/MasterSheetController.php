<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\GradeEntry;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\SubjectAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterSheetController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $schoolYears = SchoolYear::query()->orderByDesc('name')->get();
        $gradeLevels = Section::query()
            ->select('grade_level')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level')
            ->map(fn ($level) => (int) $level)
            ->values();

        $selectedGradeLevel = (int) ($request->integer('grade_level') ?: ((int) ($gradeLevels->first() ?? 0)));
        if (! $gradeLevels->contains($selectedGradeLevel)) {
            $selectedGradeLevel = (int) ($gradeLevels->first() ?? 0);
        }

        $sections = Section::query()
            ->orderedForDropdown()
            ->when($selectedGradeLevel > 0, fn ($q) => $q->where('grade_level', $selectedGradeLevel))
            ->get();

        $selectedSchoolYear = (int) ($request->integer('school_year_id')
            ?: ($schoolYears->firstWhere('is_active', true)?->id ?? $schoolYears->first()?->id ?? 0));
        $selectedSection = (int) $request->integer('section_id', 0);
        if ($selectedSection !== 0 && ! $sections->contains('id', $selectedSection)) {
            $selectedSection = 0;
        }
        $quarter = max(1, min(4, (int) $request->integer('quarter', 1)));
        $selectedStrand = trim((string) $request->query('strand', 'ALL'));
        $selectedStrand = $selectedStrand !== '' ? $selectedStrand : 'ALL';
        $search = trim((string) $request->query('q', ''));

        $strandOptions = collect(['HUMSS', 'ABM', 'COOKERY/BPP', 'SMAW', 'FOP', 'CSS'])
            ->merge(
                Section::query()
                    ->whereNotNull('strand')
                    ->where('strand', '<>', '')
                    ->when($selectedGradeLevel > 0, fn ($q) => $q->where('grade_level', $selectedGradeLevel))
                    ->distinct()
                    ->orderBy('strand')
                    ->pluck('strand')
            )
            ->filter(fn ($strand) => is_string($strand) && trim($strand) !== '')
            ->map(fn ($strand) => trim((string) $strand))
            ->unique()
            ->values();

        $enrollmentQuery = Enrollment::query()
            ->with(['student', 'section'])
            ->when($selectedSchoolYear > 0, fn ($q) => $q->where('school_year_id', $selectedSchoolYear))
            ->when(
                $selectedGradeLevel > 0,
                fn ($q) => $q->whereHas('section', fn ($sq) => $sq->where('grade_level', $selectedGradeLevel))
            )
            ->when($selectedSection > 0, fn ($q) => $q->where('section_id', $selectedSection))
            ->when($selectedStrand !== 'ALL', fn ($q) => $q->whereHas('section', fn ($sq) => $sq->where('strand', $selectedStrand)))
            ->when($search !== '', function ($q) use ($search): void {
                $q->whereHas('student', function ($sq) use ($search): void {
                    $sq->where('lrn', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            });

        $enrollments = $enrollmentQuery
            ->orderBy('section_id')
            ->orderBy('id')
            ->get();

        $subjects = SubjectAssignment::query()
            ->with('subject:id,code,title')
            ->where('school_year_id', $selectedSchoolYear)
            ->when(
                $selectedGradeLevel > 0,
                fn ($q) => $q->whereHas('section', fn ($sq) => $sq->where('grade_level', $selectedGradeLevel))
            )
            ->when($selectedSection > 0, fn ($q) => $q->where('section_id', $selectedSection))
            ->when($selectedStrand !== 'ALL', fn ($q) => $q->whereHas('section', fn ($sq) => $sq->where('strand', $selectedStrand)))
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->sortBy(fn ($subject) => (string) $subject->title)
            ->values();

        $subjectIds = $subjects->pluck('id')->all();
        $gradesByEnrollment = [];

        if ($enrollments->isNotEmpty() && ! empty($subjectIds)) {
            $gradeRows = GradeEntry::query()
                ->select([
                    'grade_entries.enrollment_id',
                    'subject_assignments.subject_id',
                    'grade_entries.quiz',
                    'grade_entries.performance_task',
                    'grade_entries.assignment',
                    'grade_entries.exam',
                    'grade_entries.quarter_grade',
                    'grade_entries.updated_at',
                ])
                ->join('subject_assignments', 'subject_assignments.id', '=', 'grade_entries.subject_assignment_id')
                ->where('grade_entries.quarter', $quarter)
                ->where('subject_assignments.school_year_id', $selectedSchoolYear)
                ->whereIn('grade_entries.enrollment_id', $enrollments->pluck('id'))
                ->whereIn('subject_assignments.subject_id', $subjectIds)
                ->orderBy('grade_entries.updated_at')
                ->get();

            foreach ($gradeRows as $row) {
                $gradesByEnrollment[(int) $row->enrollment_id][(int) $row->subject_id] = [
                    'quarter_grade' => $row->quarter_grade !== null ? (float) $row->quarter_grade : null,
                    'complete' => $row->quiz !== null
                        && (($row->performance_task ?? $row->assignment) !== null)
                        && $row->exam !== null
                        && $row->quarter_grade !== null,
                    'date' => $row->updated_at?->format('Y-m-d'),
                ];
            }
        }

        if ((string) $request->query('export') === 'csv') {
            return $this->exportCsv($enrollments, $subjects, $gradesByEnrollment, $quarter);
        }

        $maleCount = (int) $enrollments->filter(function ($enrollment): bool {
            $sex = strtolower((string) ($enrollment->student?->sex ?? ''));

            return in_array($sex, ['m', 'male'], true);
        })->count();

        $femaleCount = (int) $enrollments->filter(function ($enrollment): bool {
            $sex = strtolower((string) ($enrollment->student?->sex ?? ''));

            return in_array($sex, ['f', 'female'], true);
        })->count();

        $blaanCount = (int) $enrollments->filter(function ($enrollment): bool {
            $ethnicity = Str::lower(trim((string) ($enrollment->student?->ethnicity ?? '')));

            return $ethnicity === 'blaan';
        })->count();

        $islamCount = (int) $enrollments->filter(function ($enrollment): bool {
            $ethnicity = Str::lower(trim((string) ($enrollment->student?->ethnicity ?? '')));

            return in_array($ethnicity, ['islam', 'muslim'], true);
        })->count();

        $missingCells = 0;
        $strandSummary = [];
        foreach ($enrollments as $enrollment) {
            $strand = trim((string) ($enrollment->section?->strand ?? 'Unspecified'));
            $strand = $strand !== '' ? $strand : 'Unspecified';

            if (! isset($strandSummary[$strand])) {
                $strandSummary[$strand] = [
                    'strand' => $strand,
                    'students' => 0,
                    'cells_total' => 0,
                    'cells_complete' => 0,
                    'grade_sum' => 0.0,
                    'grade_count' => 0,
                ];
            }

            $strandSummary[$strand]['students']++;
            $strandSummary[$strand]['cells_total'] += (int) $subjects->count();

            foreach ($subjects as $subject) {
                $cell = data_get($gradesByEnrollment, "{$enrollment->id}.{$subject->id}");
                if (! ($cell['complete'] ?? false)) {
                    $missingCells++;
                    continue;
                }

                $strandSummary[$strand]['cells_complete']++;

                if (($cell['quarter_grade'] ?? null) !== null) {
                    $strandSummary[$strand]['grade_sum'] += (float) $cell['quarter_grade'];
                    $strandSummary[$strand]['grade_count']++;
                }
            }
        }

        $strandSummary = collect($strandSummary)
            ->map(function (array $row): array {
                $completionPct = $row['cells_total'] > 0
                    ? (int) round(($row['cells_complete'] / $row['cells_total']) * 100)
                    : 0;

                return [
                    'strand' => (string) $row['strand'],
                    'students' => (int) $row['students'],
                    'completion_pct' => max(0, min(100, $completionPct)),
                    'avg' => $row['grade_count'] > 0
                        ? round($row['grade_sum'] / $row['grade_count'], 1)
                        : null,
                ];
            })
            ->sortBy('strand')
            ->values()
            ->all();

        $ethnicityBySection = $enrollments
            ->groupBy('section_id')
            ->map(function (Collection $rows): array {
                $first = $rows->first();
                $section = $first?->section
                    ? ('Grade '.$first->section->grade_level.' - '.$first->section->name)
                    : '-';

                $blaan = (int) $rows->filter(function ($enrollment): bool {
                    $ethnicity = Str::lower(trim((string) ($enrollment->student?->ethnicity ?? '')));

                    return $ethnicity === 'blaan';
                })->count();

                $islam = (int) $rows->filter(function ($enrollment): bool {
                    $ethnicity = Str::lower(trim((string) ($enrollment->student?->ethnicity ?? '')));

                    return in_array($ethnicity, ['islam', 'muslim'], true);
                })->count();

                return [
                    'section' => $section,
                    'blaan' => $blaan,
                    'islam' => $islam,
                    'total' => (int) $rows->count(),
                ];
            })
            ->sortBy('section')
            ->values()
            ->all();

        return view('master-sheet.index', [
            'schoolYears' => $schoolYears,
            'gradeLevels' => $gradeLevels,
            'selectedGradeLevel' => $selectedGradeLevel,
            'sections' => $sections,
            'strandOptions' => $strandOptions,
            'selectedSchoolYear' => $selectedSchoolYear,
            'selectedSection' => $selectedSection,
            'selectedStrand' => $selectedStrand,
            'quarter' => $quarter,
            'search' => $search,
            'enrollments' => $enrollments,
            'subjects' => $subjects,
            'gradesByEnrollment' => $gradesByEnrollment,
            'strandSummary' => $strandSummary,
            'ethnicityBySection' => $ethnicityBySection,
            'stats' => [
                'students' => (int) $enrollments->count(),
                'subjects' => (int) $subjects->count(),
                'male' => $maleCount,
                'female' => $femaleCount,
                'blaan' => $blaanCount,
                'islam' => $islamCount,
                'missing' => $missingCells,
            ],
        ]);
    }

    private function exportCsv(
        Collection $enrollments,
        Collection $subjects,
        array $gradesByEnrollment,
        int $quarter
    ): StreamedResponse {
        $filename = sprintf('master-sheet-q%d-%s.csv', $quarter, now()->format('Ymd-His'));

        return response()->streamDownload(function () use ($enrollments, $subjects, $gradesByEnrollment): void {
            $stream = fopen('php://output', 'wb');
            if (! $stream) {
                return;
            }

            // UTF-8 BOM for spreadsheet compatibility.
            fwrite($stream, "\xEF\xBB\xBF");

            $headers = ['No.', 'LRN', 'Student', 'Strand', 'Section'];
            foreach ($subjects as $subject) {
                $headers[] = (string) $subject->title;
            }
            fputcsv($stream, $headers);

            foreach ($enrollments as $index => $enrollment) {
                $row = [
                    (int) $index + 1,
                    (string) ($enrollment->student?->lrn ?? ''),
                    (string) ($enrollment->student?->full_name ?? ''),
                    (string) ($enrollment->section?->strand ?? ''),
                    $enrollment->section
                        ? ('Grade '.$enrollment->section->grade_level.' - '.$enrollment->section->name)
                        : '',
                ];

                foreach ($subjects as $subject) {
                    $cell = data_get($gradesByEnrollment, "{$enrollment->id}.{$subject->id}");
                    $row[] = ($cell['complete'] ?? false)
                        ? (string) number_format((float) ($cell['quarter_grade'] ?? 0), 0)
                        : '';
                }

                fputcsv($stream, $row);
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
