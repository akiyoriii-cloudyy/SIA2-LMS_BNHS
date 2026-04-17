<?php

namespace App\Services;

use App\Jobs\SendSmsLog;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\SmsLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function recordAttendance(
        Enrollment $enrollment,
        Carbon $attendanceDate,
        string $status,
        ?int $courseId = null,
        ?string $remarks = null,
        ?User $recordedBy = null
    ): AttendanceRecord {
        return DB::transaction(function () use ($enrollment, $attendanceDate, $status, $courseId, $remarks, $recordedBy): AttendanceRecord {
            $record = AttendanceRecord::updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'attendance_date' => $attendanceDate->toDateString(),
                ],
                [
                    'course_id' => $courseId,
                    'school_week_start' => $attendanceDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                    'status' => $status,
                    'remarks' => $remarks,
                    'recorded_by' => $recordedBy?->id,
                ]
            );

            if ($status === 'absent') {
                $this->triggerWeeklyAbsenceNotificationIfNeeded($enrollment, $attendanceDate);
            }

            return $record;
        });
    }

    private function triggerWeeklyAbsenceNotificationIfNeeded(Enrollment $enrollment, Carbon $attendanceDate): void
    {
        $weekStart = $attendanceDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $attendanceDate->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $absenceCount = AttendanceRecord::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereBetween('attendance_date', [$weekStart, $weekEnd])
            ->where('status', 'absent')
            ->count();

        if ($absenceCount < 5) {
            return;
        }

        $student = $enrollment->student()->with('guardians')->first();
        if (! $student) {
            return;
        }

        $guardians = $student->guardians
            ->filter(fn (Guardian $guardian): bool => ! empty($guardian->phone))
            ->sortByDesc(fn (Guardian $guardian): int => (int) $guardian->pivot->is_primary)
            ->filter(fn (Guardian $guardian): bool => (bool) $guardian->pivot->receive_sms);

        foreach ($guardians as $guardian) {
            $notificationKey = implode(':', [
                'weekly_absence',
                $enrollment->id,
                $guardian->id,
                $weekStart,
            ]);

            if (SmsLog::query()->where('notification_key', $notificationKey)->exists()) {
                continue;
            }

            $message = sprintf(
                'BNHS Notice: %s has %d absences for week starting %s. Please coordinate with the school.',
                $student->full_name,
                $absenceCount,
                Carbon::parse($weekStart)->format('M d, Y')
            );

            $log = SmsLog::create([
                'guardian_id' => $guardian->id,
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'week_start' => $weekStart,
                'absences_count' => $absenceCount,
                'phone_number' => $guardian->phone,
                'message' => $message,
                'notification_key' => $notificationKey,
                'status' => 'queued',
            ]);

            SendSmsLog::dispatch($log->id)->afterCommit();
        }
    }
}
