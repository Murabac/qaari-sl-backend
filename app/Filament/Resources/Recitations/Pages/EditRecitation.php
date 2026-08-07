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
                ->action(function (AyahTimingSyncService $sync) use ($record): void {
                    try {
                        $toolchain = $sync->toolchainReady();

                        if (! $toolchain['ready']) {
                            Notification::make()
                                ->title('Matching tools not ready')
                                ->body('Ask your developer to finish setup (FFmpeg), then try again.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $sync->sync($record->fresh(['surah']));

                        Notification::make()
                            ->title('Text matched')
                            ->body('Listeners can now follow along. Adjust anything that feels off below.')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Could not match text')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }

                    $this->redirect(static::getUrl(['record' => $record]), navigate: true);
                }),
            Action::make('replaceManualWithAutoSync')
                ->label('Start over with automatic matching')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->visible(fn (): bool => filled($record->audio_url) && $record->sync_method === 'manual')
                ->requiresConfirmation()
                ->modalHeading('Start over?')
                ->modalDescription('This clears the ayah marks you set by hand and tries automatic matching instead. Only do this if you’re sure.')
                ->modalSubmitActionLabel('Yes, start over')
                ->action(function (AyahTimingSyncService $sync) use ($record): void {
                    try {
                        $toolchain = $sync->toolchainReady();

                        if (! $toolchain['ready']) {
                            Notification::make()
                                ->title('Matching tools not ready')
                                ->body('Ask your developer to finish setup (FFmpeg), then try again.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $sync->sync($record->fresh(['surah']), overwriteManual: true);

                        Notification::make()
                            ->title('Started over')
                            ->body('Automatic matching replaced your hand-marked ayahs.')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Could not match text')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }

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

                    $this->redirect(static::getUrl(['record' => $record]), navigate: true);
                }),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  list<float|int|string>  $startsSeconds
     */
    public function saveManualTimings(array $startsSeconds, ?int $resumeAyah = null): void
    {
        /** @var Recitation $record */
        $record = $this->getRecord();

        try {
            app(AyahTimingSyncService::class)->saveManualTimings(
                $record->fresh(['surah', 'ayahTimings']),
                $startsSeconds,
                $resumeAyah,
            );

            Notification::make()
                ->title('Progress saved')
                ->body(
                    $resumeAyah
                        ? "All set. Next time we’ll open ayah {$resumeAyah} for you."
                        : 'All set. You can leave and continue later anytime.'
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Could not save timings')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        // Stay on the page so admins can keep working; refresh record for resume marker.
        $this->record = $record->fresh(['ayahTimings', 'surah.ayahs']);
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

        if (is_string($audio) && $audio === $record->audio_url) {
            $data['duration'] = $record->duration;
            $data['file_size'] = $record->file_size;

            return $data;
        }

        $meta = AudioMetadata::fromUpload($data['audio_url'] ?? null, 'r2');

        $data['duration'] = $meta['duration'] ?? $record->duration;
        $data['file_size'] = $meta['file_size'] ?? $record->file_size;

        return $data;
    }
}
