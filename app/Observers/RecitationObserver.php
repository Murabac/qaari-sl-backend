<?php

namespace App\Observers;

use App\Enums\SyncStatus;
use App\Jobs\SyncRecitationAyahTimingsJob;
use App\Models\Recitation;

class RecitationObserver
{
    public function created(Recitation $recitation): void
    {
        $this->queueSyncIfNeeded($recitation, audioChanged: true);
    }

    public function updated(Recitation $recitation): void
    {
        $audioChanged = $recitation->wasChanged('audio_url');
        $surahChanged = $recitation->wasChanged('surah_id');

        if ($audioChanged || $surahChanged) {
            $recitation->forceFill([
                'sync_status' => SyncStatus::Pending,
                'synced_at' => null,
                'sync_error' => null,
                'sync_method' => null,
                'manual_sync_ayah' => null,
            ])->saveQuietly();

            $recitation->ayahTimings()->delete();
        }

        $this->queueSyncIfNeeded($recitation, audioChanged: $audioChanged || $surahChanged);
    }

    private function queueSyncIfNeeded(Recitation $recitation, bool $audioChanged): void
    {
        if (! config('ayah_sync.auto_sync', true)) {
            return;
        }

        if (! $audioChanged && $recitation->sync_status === SyncStatus::Synced) {
            return;
        }

        if (blank($recitation->audio_url)) {
            return;
        }

        SyncRecitationAyahTimingsJob::dispatch($recitation->id)->afterResponse();
    }
}
