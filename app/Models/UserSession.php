<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'location',
        'started_at',
        'last_activity_at',
        'is_active',
        'ended_at',
        'end_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsEnded(string $reason = 'logout'): void
    {
        $this->update([
            'is_active' => false,
            'ended_at' => now(),
            'end_reason' => $reason,
        ]);
    }

    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
