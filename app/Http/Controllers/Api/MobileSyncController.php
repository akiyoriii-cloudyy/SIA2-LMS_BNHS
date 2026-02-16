<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\SyncBatch;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileSyncController extends Controller
{
    public function bootstrap(): JsonResponse
    {
        $activeSchoolYear = SchoolYear::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        $sections = Section::query()
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level', 'track', 'strand']);

        return response()->json([
            'active_school_year' => $activeSchoolYear ? [
                'id' => $activeSchoolYear->id,
                'name' => $activeSchoolYear->name,
                'is_active' => (bool) $activeSchoolYear->is_active,
            ] : null,
            'sections' => $sections,
        ]);
    }

    public function courses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_year_id' => ['nullable', 'exists:school_years,id'],
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $schoolYearId = $validated['school_year_id'] ?? SchoolYear::query()->where('is_active', true)->value('id');
        if (! $schoolYearId) {
            return response()->json(['message' => 'No active school year found.'], 422);
        }

        $courses = Course::query()
            ->with(['subject', 'teacher', 'materials', 'assignments'])
            ->where('school_year_id', $schoolYearId)
            ->where('section_id', $validated['section_id'])
            ->where('is_published', true)
            ->get();

        return response()->json(['data' => $courses]);
    }

    public function roster(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_year_id' => ['nullable', 'exists:school_years,id'],
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $schoolYearId = $validated['school_year_id'] ?? SchoolYear::query()->where('is_active', true)->value('id');
        if (! $schoolYearId) {
            return response()->json(['message' => 'No active school year found.'], 422);
        }

        $enrollments = Enrollment::query()
            ->with(['student.guardians'])
            ->where('school_year_id', $schoolYearId)
            ->where('section_id', $validated['section_id'])
            ->get();

        return response()->json([
            'data' => $enrollments->map(function (Enrollment $enrollment): array {
                return [
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $enrollment->student_id,
                    'student_name' => $enrollment->student?->full_name,
                    'guardians' => $enrollment->student?->guardians->map(fn ($guardian): array => [
                        'name' => trim($guardian->first_name.' '.$guardian->last_name),
                        'phone' => $guardian->phone,
                        'relationship' => $guardian->pivot->relationship,
                        'is_primary' => (bool) $guardian->pivot->is_primary,
                    ])->values() ?? [],
                ];
            })->values(),
        ]);
    }

    public function syncAttendance(Request $request, AttendanceService $attendanceService): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:120'],
            'batch_uuid' => ['required', 'uuid'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.enrollment_id' => ['required', 'exists:enrollments,id'],
            'records.*.attendance_date' => ['required', 'date'],
            'records.*.status' => ['required', Rule::in(['present', 'late', 'absent', 'excused'])],
            'records.*.course_id' => ['nullable', 'exists:courses,id'],
            'records.*.remarks' => ['nullable', 'string'],
        ]);

        $alreadySynced = SyncBatch::query()->where('batch_uuid', $validated['batch_uuid'])->exists();
        if ($alreadySynced) {
            return response()->json(['message' => 'Batch already processed.'], 200);
        }

        DB::transaction(function () use ($validated, $request, $attendanceService): void {
            foreach ($validated['records'] as $record) {
                $enrollment = Enrollment::query()->find($record['enrollment_id']);
                if (! $enrollment) {
                    continue;
                }

                $attendanceService->recordAttendance(
                    $enrollment,
                    Carbon::parse($record['attendance_date']),
                    $record['status'],
                    $record['course_id'] ?? null,
                    $record['remarks'] ?? null,
                    $request->user()
                );
            }

            SyncBatch::create([
                'user_id' => $request->user()->id,
                'device_id' => $validated['device_id'],
                'batch_uuid' => $validated['batch_uuid'],
                'payload' => $validated['records'],
                'status' => 'processed',
                'synced_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Attendance synced successfully.']);
    }
}
