<?php

namespace App\Filament\Resources\Recitations\Pages;

use App\Enums\RecitationStatus;
use App\Enums\SyncStatus;
use App\Filament\Concerns\InteractsWithAyahSyncPanel;
use App\Filament\Concerns\SkipsRenderAfterSuccessfulSave;
use App\Filament\Resources\Recitations\RecitationResource;
use App\Jobs\SyncRecitationAyahTimingsJob;
use App\Models\Recitation;
use App\Support\AudioMetadata;
use App\Support\AyahSyncProgress;
use App\Support\MediaUrl;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditRecitation extends EditRecord
{
    use InteractsWithAyahSyncPanel;
    use SkipsRenderAfterSuccessfulSave;

    protected static string $resource = RecitationResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                $this->getRelationManagersContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        // Sync panel is a View on this page (not nested Livewire) so wire:click
        // / wire:poll hit EditRecitation. Keep it outside <form> remorphs.
        return Group::make([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save'),
            SchemaView::make('filament.forms.ayah-sync-panel')
                ->viewData(fn (): array => $this->ayahSyncPanelViewData())
                ->key(fn (): string => 'ayah-sync-panel-'.$this->getRecord()->getKey()),
            $this->getFormActionsContentComponent(),
        ]);
    }

    protected function redirectToEdit(Recitation $record): void
    {
        $this->redirect(RecitationResource::getUrl('edit', ['record' => $record]));
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
                ->modalDescription('We’ll listen to the recording and try to mark when each ayah begins. You can fine-tune anything afterwards. Once you save manual marks, automatic matching stays off for this recitation.')
                ->modalSubmitActionLabel('Match automatically')
                ->action(function () use ($record): void {
                    if ($record->fresh()?->sync_method === 'manual') {
                        Notification::make()
                            ->title('Auto sync disabled')
                            ->body('This recitation has manual ayah marks. Automatic matching stays off.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $record->update([
                        'sync_status' => SyncStatus::Syncing,
                        'sync_error' => null,
                    ]);

                    AyahSyncProgress::queued($record->id);
                    SyncRecitationAyahTimingsJob::dispatch($record->id);

                    Notification::make()
                        ->title('Matching started in the background')
                        ->body('Watch the progress bar on the ayah sync panel below — it updates while the job runs.')
                        ->success()
                        ->send();

                    $this->redirectToEdit($record);
                }),
            Action::make('queueSync')
                ->label('Match in the background')
                ->icon('heroicon-o-queue-list')
                ->color('gray')
                ->visible(fn (): bool => filled($record->audio_url)
                    && $record->sync_status !== SyncStatus::Syncing
                    && $record->sync_method !== 'manual')
                ->action(function () use ($record): void {
                    if ($record->fresh()?->sync_method === 'manual') {
                        Notification::make()
                            ->title('Auto sync disabled')
                            ->body('This recitation has manual ayah marks. Automatic matching stays off.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $record->update([
                        'sync_status' => SyncStatus::Syncing,
                        'sync_error' => null,
                    ]);

                    AyahSyncProgress::queued($record->id);
                    SyncRecitationAyahTimingsJob::dispatch($record->id);

                    Notification::make()
                        ->title('Matching started in the background')
                        ->body('Progress updates live on the ayah sync panel — you can keep editing other fields.')
                        ->success()
                        ->send();

                    $this->redirectToEdit($record);
                }),
            Action::make('submitForReview')
                ->label('Submit for review')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => Auth::user()?->can('submit', $this->getRecord()) ?? false)
                ->action(function (): void {
                    /** @var Recitation $record */
                    $record = $this->getRecord();

                    $record->update([
                        'status' => RecitationStatus::PendingReview,
                        'submitted_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Submitted for review')
                        ->success()
                        ->send();

                    // Full redirect closes the confirm modal and refreshes header actions.
                    $this->redirect(RecitationResource::getUrl('index'));
                }),
            Action::make('reopen')
                ->label('Reopen to draft')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => Auth::user()?->can('reopen', $this->getRecord()) ?? false)
                ->action(function (): void {
                    /** @var Recitation $record */
                    $record = $this->getRecord();

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

                    $this->redirect(RecitationResource::getUrl('edit', ['record' => $record]));
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
