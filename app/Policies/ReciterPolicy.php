<?php

namespace App\Policies;

use App\Models\Reciter;
use App\Models\User;

class ReciterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isReviewer() || $user->isProduction();
    }

    public function view(User $user, Reciter $reciter): bool
    {
        return $user->isReviewer() || $reciter->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->isReviewer() || $user->isProduction();
    }

    public function update(User $user, Reciter $reciter): bool
    {
        return $user->isReviewer() || $reciter->isOwnedBy($user);
    }

    public function delete(User $user, Reciter $reciter): bool
    {
        return $user->isReviewer() || $reciter->isOwnedBy($user);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isReviewer() || $user->isProduction();
    }
}
