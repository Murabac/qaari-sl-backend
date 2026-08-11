<?php

namespace App\Filament\Resources\Recitations\Pages;

use App\Enums\RecitationStatus;
use App\Enums\SyncStatus;
use App\Filament\Resources\Recitations\RecitationResource;
use App\Jobs\SyncRecitationAyahTimingsJob;
use App\Models\Recitation;
use App\Services\AyahTimingSyncService;
use App\Support\AudioMetadata;
use App\Support\MediaUrl;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Renderless;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class EditRecitation extends EditRecord
{
    protected static string $resource = RecitationResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Recitation $record */
        $record = $this->getRecord();

        return [
            Action::make('playAudio')
                ->label('Play audio')
                ->icon('heroicon-o-speaker-wave')
                ->url(fn (): ?string => MediaUrl::temporary('r2', $record->audio_url))
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($record->audio_url)),
            Action::make('syncText')
                ->label('Match text automatically')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->visible(fn (): bool => filled($record->audio_url) && $record->sync_method !== 'manual')
                ->requiresConfirmation()
                ->modalHeading('Match text automatically?')
                ->modalDescription('We’ll listen to the recording and try to mark when each ayah begins. You can fine-tune anything afterwards.')
                ->modalSubmitActionLabel('Match automatically')
                ->action(function () use ($record): void {
                    $record->update([
                        'sync_status' => SyncStatus::Pending,
                        'sync_error' => null,
                    ]);

                    SyncRecitationAyahTimingsJob::dispatch($record->id);

                    Notification::make()
                        ->title('Matching started in the background')
                        ->body('Automatic matching can take a few minutes on the server. Refresh this page later to see the result.')
                        ->success()
                        ->send();

                    $this->stripHeavyRecordRelations();
                    $this->redirect(static::getUrl(['record' => $record]), navigate: true);
                }),
            Action::make('replaceManualWithAutoSync')
                ->label('Start over with automatic matching')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->visible(fn (): bool => filled($record->audio_url) && $record->sync_method === 'manual')
                ->requiresConfirmation()
                ->modalHeading('Start over?')
                ->modalDescription('This clears the ayah marks you set by hand and queues automatic matching instead. Only do this if you’re sure.')
                ->modalSubmitActionLabel('Yes, start over')
                ->action(function () use ($record): void {
                    $record->forceFill([
                        'sync_status' => SyncStatus::Pending,
                        'synced_at' => null,
                        'sync_error' => null,
                        'sync_method' => null,
                        'manual_sync_ayah' => null,
                    ])->save();

                    $record->ayahTimings()->delete();

                    SyncRecitationAyahTimingsJob::dispatch($record->id);

                    Notification::make()
                        ->title('Automatic matching queued')
                        ->body('Your hand-marked ayahs were cleared. Refresh in a few minutes for the new result.')
                        ->success()
                        ->send();

                    $this->stripHeavyRecordRelations();
                    $this->redirect(static::getUrl(['record' => $record]), navigate: true);
                }),
            Action::make('queueSync')
                ->label('Match in the background')
                ->icon('heroicon-o-queue-list')
                ->color('gray')
                ->visible(fn (): bool => filled($record->audio_url)
                    && $record->sync_status !== SyncStatus::Syncing
                    && $record->sync_method !== 'manual')
                ->action(function () use ($record): void {
                    $record->update([
                        'sync_status' => SyncStatus::Pending,
                        'sync_error' => null,
                    ]);

                    SyncRecitationAyahTimingsJob::dispatch($record->id);

                    Notification::make()
                        ->title('Matching started in the background')
                        ->body('You can keep working. Refresh this page in a few minutes to see the result.')
                        ->success()
                        ->send();

                    $this->stripHeavyRecordRelations();
                    $this->redirect(static::getUrl(['record' => $record]), navigate: true);
                }),
            Action::make('submitForReview')
                ->label('Submit for review')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => Auth::user()?->can('submit', $record) ?? false)
                ->action(function () use ($record): void {
                    $record->update([
                        'status' => RecitationStatus::PendingReview,
                        'submitted_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Submitted for review')
                        ->success()
                        ->send();

                    $this->stripHeavyRecordRelations();
                    $this->redirect(static::getUrl(['record' => $record]), navigate: true);
                }),
            Action::make('reopen')
                ->label('Reopen to draft')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => Auth::user()?->can('reopen', $record) ?? false)
                ->action(function () use ($record): void {
                    $record->update([
                        'status' => RecitationStatus::Draft,
                        'submitted_at' => null,
                        'reviewed_at' => null,
                        'reviewed_by' => null,
                    ]);

                    Notification::make()
                        ->title('Reopened as draft')
                        ->success()
                        ->send();

                    $this->stripHeavyRecordRelations();
                    $this->redirect(static::getUrl(['record' => $record]), navigate: true);
                }),
            DeleteAction::make()
                ->successRedirectUrl(RecitationResource::getUrl('index')),
        ];
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        // Let Filament save without its own redirect, then force a full document
        // navigation. Coolify returns 500 when Livewire tries to morph this page
        // (R2 FileUpload + ayah panel) even though the DB write already succeeded.
        parent::save(shouldRedirect: false, shouldSendSavedNotification: $shouldSendSavedNotification);

        if ($shouldRedirect) {
            $this->redirect(static::getUrl(['record' => $this->getRecord()]), navigate: false);
        }
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    /**
     * Save hand-marked ayah starts without re-rendering the Filament page.
     * Re-rendering embeds the full surah text again and was returning 500 on Coolify.
     *
     * @param  list<float|int|string>|array<mixed>  $startsSeconds
     */
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

            /** @var Recitation $record */
            $record = $this->getRecord();

            app(AyahTimingSyncService::class)->saveManualTimings(
                $record->fresh(),
                $starts,
                $resume,
            );

            Notification::make()
                ->title('Progress saved')
                ->body(
                    $resume
                        ? "All set. Next time we’ll open ayah {$resume} for you."
                        : 'All set. You can leave and continue later anytime.'
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            Log::error('saveManualTimings failed', [
                'recitation_id' => $this->record?->getKey(),
                'starts_count' => is_array($startsSeconds) ? count($startsSeconds) : null,
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Recitation $record */
        $record = $this->getRecord();
        $audio = $data['audio_url'] ?? null;

        if (is_array($audio)) {
            $audio = $audio[0] ?? null;
        }

        $isNewUpload = $audio instanceof TemporaryUploadedFile
            || (is_string($audio) && $audio !== $record->audio_url);

        if (! $isNewUpload) {
            $data['audio_url'] = $record->audio_url;
            $data['duration'] = $record->duration;
            $data['file_size'] = $record->file_size;

            return $data;
        }

        $meta = AudioMetadata::fromUpload($data['audio_url'] ?? null, 'r2');

        $data['duration'] = $meta['duration'] ?? $record->duration;
        $data['file_size'] = $meta['file_size'] ?? $record->file_size;

        return $data;
    }

    protected function afterSave(): void
    {
        $this->stripHeavyRecordRelations();
    }

    private function stripHeavyRecordRelations(): void
    {
        if (! $this->record instanceof Recitation) {
            return;
        }

        $this->record->unsetRelation('ayahTimings');
        $this->record->unsetRelation('surah');
        $this->record->unsetRelation('reviewNotes');
        $this->record->unsetRelation('reciter');
    }
}
