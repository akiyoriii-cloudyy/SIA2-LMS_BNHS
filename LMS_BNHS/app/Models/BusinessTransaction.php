<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'transaction_type',
        'status',
        'user_id',
        'performed_by',
        'transaction_data',
        'rollback_data',
        'description',
        'error_message',
        'retry_count',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'transaction_data' => 'array',
        'rollback_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function transactionLogs(): HasMany
    {
        return $this->hasMany(TransactionLog::class, 'transaction_id', 'transaction_id');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['committed', 'rolled_back']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsCommitted(): void
    {
        $this->update([
            'status' => 'committed',
            'completed_at' => now(),
        ]);
    }

    public function markAsRolledBack(?string $reason = null): void
    {
        $this->update([
            'status' => 'rolled_back',
            'completed_at' => now(),
            'error_message' => $reason,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function getDuration(): ?float
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }
}
