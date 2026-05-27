<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'level',
    ];

    /**
     * Resolve an active role by name, restoring a soft-deleted row if present so
     * user assignments and uniqueness stay consistent.
     *
     * @param  array<string, mixed>  $attributes  Extra columns when creating a new role
     */
    public static function findRestoreOrCreate(string $name, array $attributes = []): self
    {
        $role = static::withTrashed()->where('name', $name)->first();

        if ($role !== null) {
            if ($role->trashed()) {
                $role->restore();
            }

            return $role;
        }

        return static::query()->create(array_merge(['name' => $name], $attributes));
    }

    /**
     * True if setting this role's parent to $parentId would make a loop (e.g. parent is this role or below it).
     */
    public function parentWouldCreateCycle(?int $parentId): bool
    {
        if ($parentId === null || ! $this->exists) {
            return false;
        }

        $walker = static::query()->find($parentId);
        while ($walker !== null) {
            if ($walker->id === $this->id) {
                return true;
            }
            $walker = $walker->parent;
        }

        return false;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withTrashed();
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}
