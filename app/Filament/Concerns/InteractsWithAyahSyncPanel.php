<?php

namespace App\Filament\Concerns;

use App\Enums\SyncStatus;
use App\Filament\Resources\Recitations\RecitationResource;
use App\Jobs\SyncRecitationAyahTimingsJob;
use App\Models\Ayah;
use App\Models\Recitation;
use App\Services\AyahTimingSyncService;
use App\Support\AyahSyncProgress;
use App\Support\MediaUrl;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Renderless;
use Throwable;

/**
 * Ayah sync UI lives on EditRecitation (not a nested Livewire child) so
 * Filament remorphs do not steal wire:click onto the wrong component.
 */
trait InteractsWithAyahSyncPanel
{
    public bool $ayahSyncNotifiedCompletion = false;

    public int $ayahSyncProgressTick = 0;

    /**
     * @return array<string, mixed>
     */
    public function ayahSyncPanelViewData(): array
    {
        /** @var Recitation $record */
        $record = $this->getRecord()->loadMissing(['reciter', 'surah']);

        $ayahRows = Ayah::query()
            ->where('surah_id', $record->surah_id)
            ->orderBy('number')
            ->get(['number', 'text_uthmani'])
            ->map(fn (Ayah $a): array => [
                'n' => (int) $a->number,
                't' => (string) $a->text_uthmani,
            ])
            ->values()
            ->all();

        $timingRows = $record->ayahTimings()
            ->orderBy('ayah_number')
            ->get(['ayah_number', 'start_ms', 'end_ms'])
            ->map(fn ($t): array => [
                'ayah_number' => (int) $t->ayah_number,
                'start_ms' => (int) $t->start_ms,
                'end_ms' => (int) $t->end_ms,
            ])
            ->values()
            ->all();

        $verseCount = max(1, (int) ($record->surah?->verse_count ?: count($ayahRows) ?: 1));

        $surahLabel = $record->surah
            ? trim(sprintf('%s. %s', $record->surah->number, $record->surah->name_english))
            : null;

        $progress = AyahSyncProgress::get((int) $record->id);
        $isRunning = in_array($record->sync_status, [SyncStatus::Pending, SyncStatus::Syncing], true)
            || in_array($progress['status'] ?? '', ['pending', 'syncing'], true);

        return [
            'record' => $record,
            'ayahRows' => $ayahRows,
            'timingRows' => $timingRows,
            'audioUrl' => filled($record->audio_url)
                ? MediaUrl::temporary('r2', $record->audio_url)
                : null,
            'verseCount' => $verseCount,
            'reciterName' => $record->reciter?->name_english,
            'surahLabel' => $surahLabel,
            'syncProgress' => $progress,
            'isSyncRunning' => $isRunning,
        ];
    }

    #[Renderless]
    public function saveManualTimings(mixed $startsSeconds = [], mixed $resumeAyah = null): void
    {
        try {
            $starts = collect(is_array($startsSeconds) ? $startsSeconds : [])
                ->map(fn ($value): float => round((float) $value, 3))
                ->values()
                ->all();

            $resume = ($resumeAyah === null || $resumeAyah === '')
                ? null
                : (int) $resumeAyah;

            /** @var Recitation $recitation */
            $recitation = $this->getRecord();

            app(AyahTimingSyncService::class)->saveManualTimings(
                $recitation,
                $starts,
                $resume,
            );

            $verseCount = max(1, (int) ($recitation->surah()->value('verse_count') ?: count($starts)));

            Notification::make()
                ->title('Progress saved')
                ->body(
                    $resume && $resume < $verseCount
                        ? "Progress saved — continue from ayah {$resume}"
                        : 'Ayah timings saved'
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            Log::error('EditRecitation::saveManualTimings failed', [
                'recitation_id' => $this->getRecord()->getKey(),
                'error' => $e->getMessage(),
            ]);
            report($e);

            Notification::make()
                ->title('Could not save timings')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runAutoSync(bool $overwriteManual = false): void
    {
        try {
            /** @var Recitation $recitation */
            $recitation = $this->getRecord();

            if ($recitation->sync_method === 'manual') {
                Notification::make()
                    ->title('Auto sync disabled')
                    ->body('This recitation was marked by hand. Automatic matching stays off — keep editing timings manually.')
                    ->warning()
                    ->send();

                return;
            }

            if (! filled($recitation->audio_url)) {
                Notification::make()
                    ->title('No audio file')
                    ->body('Upload audio before running auto sync.')
                    ->warning()
                    ->send();

                return;
            }

            $recitation->update([
                'sync_status' => SyncStatus::Syncing,
                'sync_error' => null,
            ]);

            AyahSyncProgress::queued((int) $recitation->id);
            SyncRecitationAyahTimingsJob::dispatch($recitation->id);
            $this->ayahSyncNotifiedCompletion = false;
            $this->ayahSyncProgressTick++;

            Notification::make()
                ->title('Automatic matching started')
                ->body('Progress will update below while the background job runs.')
                ->success()
                ->send();

            $this->redirect(RecitationResource::getUrl('edit', ['record' => $recitation]));
        } catch (Throwable $e) {
            Log::error('EditRecitation::runAutoSync failed', [
                'recitation_id' => $this->getRecord()->getKey(),
                'overwrite' => $overwriteManual,
                'error' => $e->getMessage(),
            ]);
            report($e);

            Notification::make()
                ->title('Auto sync failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function pollSyncProgress(): void
    {
        /** @var Recitation $record */
        $record = $this->getRecord()->fresh();
        if (! $record) {
            return;
        }

        $this->ayahSyncProgressTick++;

        $progress = AyahSyncProgress::get((int) $record->id);
        $status = $record->sync_status;
        $done = in_array($status, [SyncStatus::Synced, SyncStatus::Failed, SyncStatus::MissingAudio], true)
            || in_array($progress['status'] ?? '', ['synced', 'failed'], true);

        if ($done && ! $this->ayahSyncNotifiedCompletion) {
            $this->ayahSyncNotifiedCompletion = true;

            if ($status === SyncStatus::Synced || ($progress['status'] ?? '') === 'synced') {
                Notification::make()
                    ->title('Automatic matching finished')
                    ->body('Ayah timings are ready. You can fine-tune them below.')
                    ->success()
                    ->send();
            } elseif ($status === SyncStatus::Failed || ($progress['status'] ?? '') === 'failed') {
                Notification::make()
                    ->title('Automatic matching failed')
                    ->body($record->sync_error ?: ($progress['label'] ?? 'Unknown error'))
                    ->danger()
                    ->send();
            }
        }

        // Keep Filament's record in sync so badges/timings refresh on poll.
        $this->record = $record;
    }
}
