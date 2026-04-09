<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    /**
     * In-app LMS notifications (custom {@see SchoolNotification} rows; not Laravel's database notification channel).
     */
    public function schoolNotifications(): HasMany
    {
        return $this->hasMany(SchoolNotification::class, 'user_id');
    }

    public function hasRole(string ...$roles): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->whereIn('name', $roles)->isNotEmpty();
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function hasPermission(string ...$permissions): bool
    {
        if ($this->relationLoaded('roles') && $this->roles->every(fn ($role) => $role->relationLoaded('permissions'))) {
            return $this->roles
                ->flatMap(fn ($role) => $role->permissions)
                ->whereIn('name', $permissions)
                ->isNotEmpty();
        }

        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->whereIn('name', $permissions))
            ->exists();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->profile?->full_name ?: $this->name;
    }
}
