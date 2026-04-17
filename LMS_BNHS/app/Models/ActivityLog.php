<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'custom_action',
        'description',
        'details',
        'notes',
        'ip_address',
        'user_agent',
        'status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setDetailsAttribute($value): void
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['details'] = Crypt::encryptString(json_encode($value));
        } else {
            $this->attributes['details'] = $value ? Crypt::encryptString($value) : null;
        }
    }

    public function getDetailsAttribute($value): ?array
    {
        if (! $value) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);
            $decoded = json_decode($decrypted, true);

            return $decoded ?? $decrypted;
        } catch (\Exception $e) {
            return ['encrypted_data' => $value];
        }
    }
}
