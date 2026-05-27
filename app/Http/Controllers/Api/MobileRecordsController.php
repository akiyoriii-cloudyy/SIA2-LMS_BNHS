<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileRecordsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'grade_level' => ['nullable', 'integer', 'min:1', 'max:12'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'status' => ['nullable', Rule::in(['active', 'archived', ''])],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $schoolYearId = SchoolYear::query()->where('is_active', true)->value('id');

        $query = Enrollment::query()
            ->with(['student.guardians', 'section'])
            ->when($schoolYearId, fn ($q) => $q->where('school_year_id', $schoolYearId))
            ->when(! empty($validated['section_id']), fn ($q) => $q->where('section_id', (int) $validated['section_id']))
            ->when(! empty($validated['grade_level']), function ($q) use ($validated): void {
                $q->whereHas('section', fn ($sq) => $sq->where('grade_level', (int) $validated['grade_level']));
            })
            ->when(($validated['status'] ?? '') === 'active', fn ($q) => $q->whereHas('student', fn ($s) => $s->whereNull('deleted_at')))
            ->when(($validated['status'] ?? '') === 'archived', fn ($q) => $q->whereHas('student', fn ($s) => $s->whereNotNull('deleted_at')))
            ->when($search !== '', function ($q) use ($search): void {
                $q->whereHas('student', function ($s) use ($search): void {
                    $s->where('lrn', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('rfid_uid', 'like', "%{$search}%");
                });
            })
            ->orderBy('id')
            ->limit(200);

        $data = $query->get()->map(fn (Enrollment $e): array => $this->enrollmentPayload($e));

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateStudentPayload($request);

        $student = DB::transaction(function () use ($validated): Student {
            $student = Student::query()->create([
                'lrn' => $validated['lrn'] ?? null,
                'rfid_uid' => $validated['rfid_uid'] ?? null,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'sex' => $validated['sex'] ?? null,
            ]);

            Enrollment::query()->create([
                'student_id' => $student->id,
                'school_year_id' => $validated['school_year_id'],
                'section_id' => $validated['section_id'],
                'status' => 'active',
            ]);

            if (! empty($validated['guardian_name']) && ! empty($validated['guardian_phone'])) {
                $guardian = Guardian::query()->firstOrCreate(
                    ['phone' => $validated['guardian_phone']],
                    ['first_name' => $validated['guardian_name'], 'last_name' => '']
                );
                $student->guardians()->syncWithoutDetaching([
                    $guardian->id => ['relationship' => 'Guardian', 'is_primary' => true, 'receive_sms' => true],
                ]);
            }

            return $student->fresh(['guardians', 'enrollments.section']);
        });

        return response()->json(['data' => $this->studentPayload($student)], 201);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $validated = $this->validateStudentPayload($request, $student->id);

        DB::transaction(function () use ($student, $validated): void {
            $student->update([
                'lrn' => $validated['lrn'] ?? $student->lrn,
                'rfid_uid' => $validated['rfid_uid'] ?? $student->rfid_uid,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'sex' => $validated['sex'] ?? $student->sex,
            ]);

            if (! empty($validated['guardian_phone'])) {
                $guardian = Guardian::query()->firstOrCreate(
                    ['phone' => $validated['guardian_phone']],
                    ['first_name' => $validated['guardian_name'] ?? 'Guardian', 'last_name' => '']
                );
                $student->guardians()->syncWithoutDetaching([
                    $guardian->id => ['relationship' => 'Guardian', 'is_primary' => true, 'receive_sms' => true],
                ]);
            }
        });

        return response()->json(['data' => $this->studentPayload($student->fresh(['guardians', 'enrollments.section']))]);
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json(['message' => 'Student record archived.']);
    }

    private function validateStudentPayload(Request $request, ?int $ignoreStudentId = null): array
    {
        $schoolYearId = SchoolYear::query()->where('is_active', true)->value('id');

        return $request->validate([
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'lrn' => ['nullable', 'string', 'max:255', Rule::unique('students', 'lrn')->ignore($ignoreStudentId)],
            'rfid_uid' => ['nullable', 'string', 'max:100', Rule::unique('students', 'rfid_uid')->ignore($ignoreStudentId)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', Rule::in(['Male', 'Female', 'M', 'F'])],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
        ]) + ['school_year_id' => (int) ($request->integer('school_year_id') ?: $schoolYearId)];
    }

    private function enrollmentPayload(Enrollment $enrollment): array
    {
        $student = $enrollment->student;
        $guardian = $student?->guardians?->first();

        return [
            'enrollment_id' => $enrollment->id,
            'student_id' => $student?->id,
            'name' => $student?->full_name,
            'lrn' => $student?->lrn,
            'rfid_uid' => $student?->rfid_uid,
            'grade_level' => $enrollment->section?->grade_level,
            'section' => $enrollment->section?->name,
            'status' => $student?->deleted_at ? 'archived' : 'active',
            'parent_name' => $guardian ? trim($guardian->first_name.' '.$guardian->last_name) : null,
            'parent_contact' => $guardian?->phone,
        ];
    }

    private function studentPayload(Student $student): array
    {
        $enrollment = $student->enrollments->first();
        $guardian = $student->guardians->first();

        return [
            'student_id' => $student->id,
            'name' => $student->full_name,
            'lrn' => $student->lrn,
            'rfid_uid' => $student->rfid_uid,
            'grade_level' => $enrollment?->section?->grade_level,
            'section' => $enrollment?->section?->name,
            'status' => $student->deleted_at ? 'archived' : 'active',
            'parent_name' => $guardian ? trim($guardian->first_name.' '.$guardian->last_name) : null,
            'parent_contact' => $guardian?->phone,
        ];
    }
}
