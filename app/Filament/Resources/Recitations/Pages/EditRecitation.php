<?php

namespace App\Filament\Resources\Recitations\Pages;

use App\Enums\RecitationStatus;
use App\Filament\Resources\Recitations\RecitationResource;
use App\Models\Recitation;
use App\Support\AudioMetadata;
use App\Support\MediaUrl;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $meta = AudioMetadata::fromUpload($data['audio_url'] ?? null, 'r2');

        if ($meta['duration'] !== null) {
            $data['duration'] = $meta['duration'];
        }

        if ($meta['file_size'] !== null) {
            $data['file_size'] = $meta['file_size'];
        }

        return $data;
    }
}
