<?php

namespace App\Services;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AccountDeletionService
{
    /**
     * Permanently delete a listener account and associated personal data.
     * Staff accounts cannot be deleted through this self-service flow.
     */
    public function delete(User $user): void
    {
        if ($user->hasAnyRole([
            StaffRole::SuperAdmin->value,
            StaffRole::Admin->value,
            StaffRole::Production->value,
        ])) {
            throw new RuntimeException('Staff accounts cannot be deleted through self-service.');
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->favorites()->delete();

            foreach ($user->playlists()->get() as $playlist) {
                $playlist->items()->delete();
                $playlist->delete();
            }

            $user->syncRoles([]);
            $user->delete();
        });
    }
}
