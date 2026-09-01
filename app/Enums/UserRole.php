<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Staf = 'staf';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Admin => 'Admin',
            self::Staf => 'Staf',
        };
    }

    public function canManageUsers(): bool
    {
        return $this === self::Superadmin;
    }

    public function canManageCatalog(): bool
    {
        return $this === self::Superadmin || $this === self::Admin;
    }
}
