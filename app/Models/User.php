<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, RecordsActivity, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'login_failed_attempts' => 'integer',
            'login_locked_at' => 'datetime',
        ];
    }

    public function isLoginLocked(): bool
    {
        return $this->login_locked_at !== null;
    }

    public function registerFailedLogin(): bool
    {
        $attempts = min(255, ((int) $this->login_failed_attempts) + 1);
        $locked = $attempts >= (int) config('admin-auth.max_attempts', 3);

        $this->forceFill([
            'login_failed_attempts' => $attempts,
            'login_locked_at' => $locked ? now() : null,
        ])->saveQuietly();

        return $locked;
    }

    public function unlockLogin(): void
    {
        $this->forceFill([
            'login_failed_attempts' => 0,
            'login_locked_at' => null,
        ])->saveQuietly();
    }

    public function resolvedRole(): UserRole
    {
        return $this->role ?? UserRole::Admin;
    }

    public function isSuperadmin(): bool
    {
        return $this->resolvedRole() === UserRole::Superadmin;
    }

    public function isAdmin(): bool
    {
        return $this->resolvedRole() === UserRole::Admin;
    }

    public function isStaff(): bool
    {
        return $this->resolvedRole() === UserRole::Staf;
    }

    public function canManageCatalog(): bool
    {
        return $this->resolvedRole()->canManageCatalog();
    }

    public function canSeeLeadSources(): bool
    {
        return ! $this->isStaff();
    }
}
