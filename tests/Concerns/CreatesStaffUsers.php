<?php

namespace Tests\Concerns;

use App\Enums\StaffRole;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

trait CreatesStaffUsers
{
    protected function seedStaffRoles(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (StaffRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }

    protected function makeStaffUser(StaffRole $role, array $overrides = []): User
    {
        $this->seedStaffRoles();

        $defaults = match ($role) {
            StaffRole::SuperAdmin => ['name' => 'Super Admin', 'email' => 'admin@qaarisl.com'],
            StaffRole::Admin => ['name' => 'Review Admin', 'email' => 'reviewer@qaarisl.com'],
            StaffRole::Production => ['name' => 'Production User', 'email' => 'production@qaarisl.com'],
        };

        $user = User::query()->create(array_merge($defaults, [
            'password' => 'password',
        ], $overrides));

        $user->syncRoles([$role->value]);

        return $user;
    }
}
