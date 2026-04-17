<?php

namespace App\Jobs;

use App\Models\SchoolNotification;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendSmsLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(public int $smsLogId)
    {
    }

    public function handle(SmsService $smsService): void
    {
        $locked = SmsLog::query()
            ->whereKey($this->smsLogId)
            ->where('status', 'queued')
            ->update(['status' => 'sending']);

        $log = SmsLog::query()->find($this->smsLogId);
        if (! $log) {
            return;
        }

        if ($locked === 0) {
            if ($log->status !== 'sending') {
                return;
            }

            if ($log->updated_at && $log->updated_at->gt(now()->subMinutes(5))) {
                return;
            }
        }

        try {
            $result = $smsService->send($log->phone_number, $log->message);

            $log->update([
                'provider_message_id' => $result['id'] ?? null,
                'status' => $result['status'] ?? 'sent',
                'error_message' => null,
                'sent_at' => now(),
            ]);

            SchoolNotification::create([
                'student_id' => $log->student_id,
                'type' => 'weekly_absence_alert',
                'channel' => 'sms',
                'title' => 'Weekly Absence Alert',
                'message' => $log->message,
                'meta' => [
                    'sms_log_id' => $log->id,
                    'week_start' => $log->week_start?->toDateString(),
                    'absences_count' => $log->absences_count,
                ],
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}

