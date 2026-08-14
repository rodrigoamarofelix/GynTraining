<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'phone', 'avatar', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function gyms(): BelongsToMany
    {
        return $this->belongsToMany(Gym::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function trainer(): HasOne
    {
        return $this->hasOne(Trainer::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()->whereHas('permissions', fn ($query) => $query->where('slug', $permission))->exists();
    }

    public function hasRole(RoleName|string $role): bool
    {
        $slug = $role instanceof RoleName ? $role->value : $role;

        return $this->roles()->where('slug', $slug)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        $slugs = array_map(
            fn (RoleName|string $role) => $role instanceof RoleName ? $role->value : $role,
            $roles,
        );

        return $this->roles()->whereIn('slug', $slugs)->exists();
    }

    public function assignRole(RoleName|string $role): void
    {
        $slug = $role instanceof RoleName ? $role->value : $role;
        $roleModel = Role::query()->where('slug', $slug)->firstOrFail();

        $this->roles()->syncWithoutDetaching([$roleModel->id]);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }
}
