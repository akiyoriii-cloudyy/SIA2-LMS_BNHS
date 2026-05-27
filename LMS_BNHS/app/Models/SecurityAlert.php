<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_type',
        'severity',
        'title',
        'description',
        'trigger_data',
        'target_type',
        'target_value',
        'is_active',
        'is_acknowledged',
        'acknowledged_at',
        'acknowledged_by',
        'acknowledgment_notes',
        'occurrence_count',
        'first_occurrence_at',
        'last_occurrence_at',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'is_active' => 'boolean',
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'first_occurrence_at' => 'datetime',
        'last_occurrence_at' => 'datetime',
    ];

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUnacknowledged($query)
    {
        return $query->where('is_acknowledged', false);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    public function acknowledge(int $acknowledgedBy, ?string $notes = null): void
    {
        $this->update([
            'is_acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => $acknowledgedBy,
            'acknowledgment_notes' => $notes,
        ]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function incrementOccurrence(): void
    {
        $this->update([
            'occurrence_count' => $this->occurrence_count + 1,
            'last_occurrence_at' => now(),
        ]);
    }
}
