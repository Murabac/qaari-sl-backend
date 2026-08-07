<?php

namespace App\Jobs;

use App\Models\Recitation;
use App\Services\AyahTimingSyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
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
            return;
        }

        // Never let background auto-sync wipe admin/user manual timings.
        if ($recitation->sync_method === 'manual') {
            Log::info('Skipping ayah sync — manual timings present', [
                'recitation_id' => $this->recitationId,
            ]);

            return;
        }

        $sync->sync($recitation);
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Ayah timing sync failed', [
            'recitation_id' => $this->recitationId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
