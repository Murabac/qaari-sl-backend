<?php

namespace App\Enums;

enum StaffRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Production = 'production';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Production => 'Production',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->label()])
            ->all();
    }
}
