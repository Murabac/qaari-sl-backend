<?php

namespace App\Observers;

use App\Enums\SyncStatus;
use App\Jobs\SyncRecitationAyahTimingsJob;
use App\Models\Recitation;
use App\Support\AyahSyncProgress;
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
            // Once ayahs were marked by hand, keep that lock even if audio/surah changes —
            // staff must re-mark manually; never re-enable automatic matching.
            $keepManual = $recitation->getOriginal('sync_method') === 'manual'
                || $recitation->sync_method === 'manual';

            $recitation->forceFill([
                'sync_status' => SyncStatus::Pending,
                'synced_at' => null,
                'sync_error' => null,
                'sync_method' => $keepManual ? 'manual' : null,
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

        // Manual timings permanently disable background auto-sync for this recitation.
        if ($recitation->sync_method === 'manual') {
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

            $recitation->forceFill([
                'sync_status' => SyncStatus::Syncing,
                'sync_error' => null,
            ])->saveQuietly();

            AyahSyncProgress::queued($recitation->id);

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
