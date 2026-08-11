<?php

namespace App\Filament\Resources\Recitations\Pages;

use App\Enums\RecitationStatus;
use App\Enums\SyncStatus;
use App\Filament\Concerns\SkipsRenderAfterSuccessfulSave;
use App\Filament\Resources\Recitations\RecitationResource;
use App\Jobs\SyncRecitationAyahTimingsJob;
use App\Livewire\RecitationAyahSyncPanel;
use App\Models\Recitation;
use App\Support\AudioMetadata;
use App\Support\MediaUrl;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Livewire as LivewireSchemaComponent;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditRecitation extends EditRecord
{
    use SkipsRenderAfterSuccessfulSave;

    protected static string $resource = RecitationResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                // Nested Livewire keeps ~6k ayah rows out of the edit-page snapshot.
                LivewireSchemaComponent::make(RecitationAyahSyncPanel::class)
                    ->lazy()
                    ->key(fn (): string => 'ayah-sync-'.$this->getRecord()->getKey()),
                $this->getRelationManagersContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                $this->getFormActionsContentComponent(),
            ]);
    }

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

                    $this->skipRender();
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

                    $this->skipRender();
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

                    $this->skipRender();
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

                    $this->skipRender();
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

                    $this->skipRender();
                }),
            DeleteAction::make()
                ->successRedirectUrl(RecitationResource::getUrl('index')),
        ];
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
}
