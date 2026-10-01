<?php

namespace App\Jobs;

use App\Enums\SyncStatus;
use App\Models\Recitation;
use App\Services\AyahTimingSyncService;
use App\Support\AyahSyncProgress;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SyncRecitationAyahTimingsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public int $uniqueFor = 600;

    public function __construct(public int $recitationId) {}

    public function uniqueId(): string
    {
        return 'ayah-sync-'.$this->recitationId;
    }

    public function handle(AyahTimingSyncService $sync): void
    {
        $recitation = Recitation::query()->with('surah')->find($this->recitationId);

        if (! $recitation) {
            AyahSyncProgress::clear($this->recitationId);

            return;
        }

        // Never let background auto-sync wipe admin/user manual timings.
        if ($recitation->sync_method === 'manual') {
            Log::info('Skipping ayah sync — manual timings present', [
                'recitation_id' => $this->recitationId,
            ]);
            AyahSyncProgress::put($this->recitationId, [
                'label' => 'Skipped — manual timings lock auto sync off.',
                'percent' => 100,
                'status' => 'synced',
            ]);

            return;
        }

        AyahSyncProgress::put($this->recitationId, [
            'label' => 'Worker picked up the job…',
            'percent' => 12,
            'status' => 'syncing',
        ]);

        $sync->sync($recitation);
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Ayah timing sync failed', [
            'recitation_id' => $this->recitationId,
            'error' => $exception?->getMessage(),
        ]);

        Recitation::query()->whereKey($this->recitationId)->update([
            'sync_status' => SyncStatus::Failed,
            'sync_error' => Str::limit($exception?->getMessage() ?? 'Background sync failed.', 1000),
            'synced_at' => null,
        ]);

        AyahSyncProgress::put($this->recitationId, [
            'label' => 'Matching failed: '.Str::limit($exception?->getMessage() ?? 'Unknown error', 160),
            'percent' => 100,
            'status' => 'failed',
        ]);
    }
}
