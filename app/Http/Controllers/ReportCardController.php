<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\SubjectAssignment;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportCardController extends Controller
{
    private const OBSERVED_VALUE_SCALE = ['AO', 'SO', 'RO', 'NO'];
    private const OBSERVED_VALUE_QUARTERS = ['q1', 'q2', 'q3', 'q4'];

    public function index(Request $request, GradingService $gradingService): View
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

        $selectedSchoolYear = (int) ($request->integer('school_year_id') ?: ($schoolYears->first()?->id ?? 0));
        $requestedSection = (int) $request->integer('section_id');
        $selectedSection = $sections->contains('id', $requestedSection)
            ? $requestedSection
            : (int) ($sections->first()?->id ?? 0);

        $enrollments = Enrollment::query()
            ->with(['student', 'reportCard'])
            ->where('school_year_id', $selectedSchoolYear)
            ->where('section_id', $selectedSection)
            ->orderBy('id')
            ->get();

        foreach ($enrollments->whereNull('reportCard') as $enrollment) {
            $gradingService->syncEnrollmentReportCard($enrollment);
        }

        $enrollments->load('reportCard');

        return view('report-cards.index', [
            'schoolYears' => $schoolYears,
            'gradeLevels' => $gradeLevels,
            'selectedGradeLevel' => $selectedGradeLevel,
            'sections' => $sections,
            'selectedSchoolYear' => $selectedSchoolYear,
            'selectedSection' => $selectedSection,
            'enrollments' => $enrollments,
        ]);
    }

    public function show(Request $request, Enrollment $enrollment, GradingService $gradingService): View
    {
        $enrollment->load(['student', 'section', 'schoolYear']);
        $gradingService->syncEnrollmentReportCard($enrollment);

        $enrollment->load([
            'reportCard.items.subjectAssignment.subject',
            'reportCard.items.subjectAssignment.section',
        ]);

        $peerEnrollments = Enrollment::query()
            ->with('student')
            ->where('school_year_id', $enrollment->school_year_id)
            ->where('section_id', $enrollment->section_id)
            ->orderBy('id')
            ->get();

        $adviserTeacher = SubjectAssignment::query()
            ->with(['teacher.user'])
            ->where('school_year_id', $enrollment->school_year_id)
            ->where('section_id', $enrollment->section_id)
            ->whereNotNull('teacher_id')
            ->orderBy('id')
            ->first()?->teacher;

        $schoolYearName = (string) ($enrollment->schoolYear?->name ?? '');
        $startYear = (int) (explode('-', $schoolYearName)[0] ?? 0);
        $syStart = $startYear > 0 ? Carbon::create($startYear, 6, 1)->startOfDay() : now()->subMonths(10)->startOfMonth();
        $syEnd = $startYear > 0 ? Carbon::create($startYear + 1, 3, 31)->endOfDay() : now()->endOfMonth();

        $attendanceRows = AttendanceRecord::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereBetween('attendance_date', [$syStart->toDateString(), $syEnd->toDateString()])
            ->get(['attendance_date', 'status']);

        $months = [
            ['key' => 'Jun', 'month' => 6],
            ['key' => 'Jul', 'month' => 7],
            ['key' => 'Aug', 'month' => 8],
            ['key' => 'Sep', 'month' => 9],
            ['key' => 'Oct', 'month' => 10],
            ['key' => 'Nov', 'month' => 11],
            ['key' => 'Dec', 'month' => 12],
            ['key' => 'Jan', 'month' => 1],
            ['key' => 'Feb', 'month' => 2],
            ['key' => 'Mar', 'month' => 3],
        ];

        $attendanceSummary = collect($months)->mapWithKeys(function (array $m) use ($attendanceRows): array {
            $filtered = $attendanceRows->filter(function ($row) use ($m): bool {
                $d = $row->attendance_date instanceof Carbon ? $row->attendance_date : Carbon::parse((string) $row->attendance_date);

                return (int) $d->month === (int) $m['month'];
            });

            $schoolDays = (int) $filtered->pluck('attendance_date')->unique()->count();
            $presentDays = (int) $filtered->where('status', 'present')->pluck('attendance_date')->unique()->count();
            $absentDays = (int) $filtered->where('status', 'absent')->pluck('attendance_date')->unique()->count();

            return [
                (string) $m['key'] => [
                    'school_days' => $schoolDays,
                    'present_days' => $presentDays,
                    'absent_days' => $absentDays,
                ],
            ];
        })->all();

        $page = (string) $request->query('page', 'outside');
        if (! in_array($page, ['outside', 'inside', 'values'], true)) {
            $page = 'outside';
        }

        $observedValueRows = $this->observedValueRows();
        $observedValues = $this->normalizeObservedValues(
            (array) ($enrollment->reportCard?->observed_values ?? []),
            $observedValueRows
        );

        return view('report-cards.show', [
            'enrollment' => $enrollment,
            'reportCard' => $enrollment->reportCard,
            'peerEnrollments' => $peerEnrollments,
            'adviserTeacher' => $adviserTeacher,
            'attendanceMonths' => array_column($months, 'key'),
            'attendanceSummary' => $attendanceSummary,
            'page' => $page,
            'observedValueRows' => $observedValueRows,
            'observedValues' => $observedValues,
            'observedValueScale' => self::OBSERVED_VALUE_SCALE,
            'missingObservedValuesCount' => $this->countMissingObservedValues($observedValues),
        ]);
    }

    public function updateObservedValues(
        Request $request,
        Enrollment $enrollment,
        GradingService $gradingService
    ): RedirectResponse {
        $validated = $request->validate([
            'page' => ['nullable', 'string'],
            'observed_values' => ['nullable', 'array'],
            'observed_values.*' => ['nullable', 'array'],
            'observed_values.*.q1' => ['nullable', 'string', 'in:AO,SO,RO,NO'],
            'observed_values.*.q2' => ['nullable', 'string', 'in:AO,SO,RO,NO'],
            'observed_values.*.q3' => ['nullable', 'string', 'in:AO,SO,RO,NO'],
            'observed_values.*.q4' => ['nullable', 'string', 'in:AO,SO,RO,NO'],
        ]);

        $gradingService->syncEnrollmentReportCard($enrollment);
        $enrollment->load('reportCard');

        if (! $enrollment->reportCard) {
            return redirect()
                ->route('report-cards.show', [$enrollment->id, 'page' => 'values'])
                ->with('success', 'Unable to save learner observed values. Report card was not found.');
        }

        $normalizedObservedValues = $this->normalizeObservedValues(
            (array) ($validated['observed_values'] ?? []),
            $this->observedValueRows()
        );

        $enrollment->reportCard->update([
            'observed_values' => $normalizedObservedValues,
        ]);

        $page = (string) ($validated['page'] ?? 'values');
        if (! in_array($page, ['outside', 'inside', 'values'], true)) {
            $page = 'values';
        }

        return redirect()
            ->route('report-cards.show', [$enrollment->id, 'page' => $page])
            ->with('success', 'Learner observed values were saved to the report card.');
    }

    /**
     * @return array<int, array{label: string, rows: array<int, array{key: string, statement: string}>}>
     */
    private function observedValueRows(): array
    {
        return [
            [
                'label' => '1. Maka-Diyos',
                'rows' => [
                    [
                        'key' => 'maka_diyos_1',
                        'statement' => 'Expresses one\'s spiritual beliefs while respecting the spiritual beliefs of others',
                    ],
                    [
                        'key' => 'maka_diyos_2',
                        'statement' => 'Shows adherence to ethical principles by upholding truth',
                    ],
                ],
            ],
            [
                'label' => '2. Makatao',
                'rows' => [
                    [
                        'key' => 'makatao_1',
                        'statement' => 'Is sensitive to individual, social, and cultural differences',
                    ],
                    [
                        'key' => 'makatao_2',
                        'statement' => 'Demonstrates contributions towards solidarity',
                    ],
                ],
            ],
            [
                'label' => '3. Maka-kalikasan',
                'rows' => [
                    [
                        'key' => 'maka_kalikasan_1',
                        'statement' => 'Cares for the environment and utilizes resources wisely, judiciously, and economically',
                    ],
                    [
                        'key' => 'maka_kalikasan_2',
                        'statement' => 'Demonstrates appropriate behavior in carrying out activities in school, community, and country',
                    ],
                ],
            ],
            [
                'label' => '4. Makabansa',
                'rows' => [
                    [
                        'key' => 'makabansa_1',
                        'statement' => 'Demonstrates pride in being a Filipino; exercises the rights and responsibilities of a Filipino citizen',
                    ],
                    [
                        'key' => 'makabansa_2',
                        'statement' => 'Demonstrates appropriate behavior in carrying out activities in the school, community, and country',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, array{label: string, rows: array<int, array{key: string, statement: string}>}>  $observedValueRows
     * @return array<string, array<string, string|null>>
     */
    private function normalizeObservedValues(array $input, array $observedValueRows): array
    {
        $normalized = [];

        foreach ($observedValueRows as $group) {
            foreach ($group['rows'] as $row) {
                $key = (string) $row['key'];
                $normalized[$key] = [];

                foreach (self::OBSERVED_VALUE_QUARTERS as $quarter) {
                    $raw = strtoupper(trim((string) ($input[$key][$quarter] ?? '')));
                    $normalized[$key][$quarter] = in_array($raw, self::OBSERVED_VALUE_SCALE, true) ? $raw : null;
                }
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, array<string, string|null>>  $observedValues
     */
    private function countMissingObservedValues(array $observedValues): int
    {
        $missing = 0;

        foreach ($observedValues as $row) {
            foreach (self::OBSERVED_VALUE_QUARTERS as $quarter) {
                if (($row[$quarter] ?? null) === null) {
                    $missing++;
                }
            }
        }

        return $missing;
    }
}
