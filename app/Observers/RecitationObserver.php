<?php

namespace App\Observers;

use App\Enums\SyncStatus;
use App\Jobs\SyncRecitationAyahTimingsJob;
use App\Models\Recitation;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        // Never auto-queue over hand-marked timings unless audio/surah actually changed.
        if ($recitation->sync_method === 'manual' && ! $audioChanged) {
            return;
        }

        if (! $audioChanged && $recitation->sync_status === SyncStatus::Synced) {
            return;
        }

        if (blank($recitation->audio_url)) {
            return;
        }

        try {
            // Always push to the async queue. With QUEUE_CONNECTION=sync the job would
            // run inline (or on terminate) and can time out Coolify's nginx after create.
            $connection = config('queue.default') === 'sync'
                ? 'database'
                : (string) config('queue.default');

            SyncRecitationAyahTimingsJob::dispatch($recitation->id)
                ->onConnection($connection);
        } catch (Throwable $e) {
            Log::warning('Could not queue ayah sync after recitation save', [
                'recitation_id' => $recitation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
