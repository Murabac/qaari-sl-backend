<?php

namespace App\Policies;

use App\Enums\MomentStatus;
use App\Models\Moment;
use App\Models\User;

class MomentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isReviewer() || $user->isProduction();
    }

    public function view(User $user, Moment $moment): bool
    {
        return $user->isReviewer() || $moment->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->isReviewer() || $user->isProduction();
    }

    public function update(User $user, Moment $moment): bool
    {
        if ($user->isReviewer()) {
            return true;
        }

        return $moment->isOwnedBy($user) && $moment->canBeEditedByProduction();
    }

    public function delete(User $user, Moment $moment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $moment->status !== MomentStatus::Approved;
        }

        return $moment->isOwnedBy($user) && $moment->canBeEditedByProduction();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isReviewer() || $user->isProduction();
    }

    public function submit(User $user, Moment $moment): bool
    {
        if ($user->isReviewer()) {
            return in_array($moment->status, [MomentStatus::Draft, MomentStatus::Rejected], true);
        }

        return $moment->isOwnedBy($user)
            && in_array($moment->status, [MomentStatus::Draft, MomentStatus::Rejected], true);
    }

    public function review(User $user, Moment $moment): bool
    {
        return $user->isReviewer() && $moment->status === MomentStatus::PendingReview;
    }

    public function reopen(User $user, Moment $moment): bool
    {
        return $user->isReviewer() && $moment->status === MomentStatus::Approved;
    }
}
