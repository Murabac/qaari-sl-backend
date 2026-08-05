<?php

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (StaffRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'admin@qaarisl.com'],
            [
                'name' => 'Abdirahman Mohamed',
                'password' => Hash::make('password'),
            ],
        );

        $superAdmin->syncRoles([StaffRole::SuperAdmin->value]);

        $admin = User::query()->updateOrCreate(
            ['email' => 'reviewer@qaarisl.com'],
            [
                'name' => 'Review Admin',
                'password' => Hash::make('password'),
            ],
        );
        $admin->syncRoles([StaffRole::Admin->value]);

        $production = User::query()->updateOrCreate(
            ['email' => 'production@qaarisl.com'],
            [
                'name' => 'Production User',
                'password' => Hash::make('password'),
            ],
        );
        $production->syncRoles([StaffRole::Production->value]);
    }
}
