<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $fillable = [
        'guardian_id',
        'student_id',
        'enrollment_id',
        'week_start',
        'absences_count',
        'phone_number',
        'message',
        'notification_key',
        'provider',
        'provider_message_id',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'week_start' => 'date',
        'sent_at' => 'datetime',
    ];

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

