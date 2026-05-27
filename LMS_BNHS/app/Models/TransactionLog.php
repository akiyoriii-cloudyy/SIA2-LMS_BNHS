<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'operation',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'sql_query',
        'execution_time_ms',
        'was_successful',
        'error_message',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'execution_time_ms' => 'float',
        'was_successful' => 'boolean',
    ];

    public function businessTransaction(): BelongsTo
    {
        return $this->belongsTo(BusinessTransaction::class, 'transaction_id', 'transaction_id');
    }

    public function scopeByOperation($query, string $operation)
    {
        return $query->where('operation', $operation);
    }

    public function scopeByTable($query, string $table)
    {
        return $query->where('table_name', $table);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('was_successful', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('was_successful', false);
    }

    public function getChanges(): array
    {
        $changes = [];
        
        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }

        return $changes;
    }
}
