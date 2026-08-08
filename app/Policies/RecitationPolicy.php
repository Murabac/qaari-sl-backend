<?php

namespace App\Policies;

use App\Enums\RecitationStatus;
use App\Models\Recitation;
use App\Models\User;

class RecitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isReviewer() || $user->isProduction();
    }

    public function view(User $user, Recitation $recitation): bool
    {
        return $user->isReviewer() || $recitation->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->isReviewer() || $user->isProduction();
    }

    public function update(User $user, Recitation $recitation): bool
    {
        if ($user->isReviewer()) {
            return true;
        }

        return $recitation->isOwnedBy($user) && $recitation->canBeEditedByProduction();
    }

    public function delete(User $user, Recitation $recitation): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $recitation->status !== RecitationStatus::Approved;
        }

        return $recitation->isOwnedBy($user) && $recitation->canBeEditedByProduction();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isReviewer() || $user->isProduction();
    }

    public function submit(User $user, Recitation $recitation): bool
    {
        if ($user->isReviewer()) {
            return in_array($recitation->status, [RecitationStatus::Draft, RecitationStatus::Rejected], true);
        }

        return $recitation->isOwnedBy($user)
            && in_array($recitation->status, [RecitationStatus::Draft, RecitationStatus::Rejected], true);
    }

    public function review(User $user, Recitation $recitation): bool
    {
        return $user->isReviewer() && $recitation->status === RecitationStatus::PendingReview;
    }

    /**
     * Manual / auto ayah timing sync — Admin & Super Admin only.
     */
    public function syncAyahs(User $user, Recitation $recitation): bool
    {
        return $user->isReviewer() && filled($recitation->audio_url);
    }

    public function reopen(User $user, Recitation $recitation): bool
    {
        return $user->isReviewer() && $recitation->status === RecitationStatus::Approved;
    }
}
