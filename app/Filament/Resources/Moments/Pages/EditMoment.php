<?php

namespace App\Filament\Resources\Moments\Pages;

use App\Enums\MomentStatus;
use App\Filament\Concerns\SkipsRenderAfterSuccessfulSave;
use App\Filament\Resources\Moments\MomentResource;
use App\Models\Moment;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditMoment extends EditRecord
{
    use SkipsRenderAfterSuccessfulSave;

    protected static string $resource = MomentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Moment $record */
        $record = $this->getRecord();

        if (is_array($data['video_url'] ?? null)) {
            $data['video_url'] = $data['video_url'][0] ?? $record->video_url;
        }
        if (is_array($data['poster_url'] ?? null)) {
            $data['poster_url'] = $data['poster_url'][0] ?? $record->poster_url;
        }

        $video = $data['video_url'] ?? null;
        if (! ($video instanceof TemporaryUploadedFile)
            && ! (is_string($video) && $video !== '')
        ) {
            $data['video_url'] = $record->video_url;
        }

        $maxDuration = (int) config('moments.max_duration_seconds', 60);
        if (isset($data['duration'])) {
            $data['duration'] = min((int) $data['duration'], $maxDuration);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        /** @var Moment $record */
        $record = $this->getRecord();

        return [
            Action::make('submitForReview')
                ->label('Submit for review')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => Auth::user()?->can('submit', $record) ?? false)
                ->action(function (): void {
                    /** @var Moment $record */
                    $record = $this->getRecord();
                    $record->update([
                        'status' => MomentStatus::PendingReview,
                        'submitted_at' => now(),
                    ]);
                    Notification::make()->title('Submitted for review')->success()->send();
                    $this->redirect(MomentResource::getUrl('index'));
                }),
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => Auth::user()?->can('review', $record) ?? false)
                ->action(function (): void {
                    /** @var Moment $record */
                    $record = $this->getRecord();
                    $record->update([
                        'status' => MomentStatus::Approved,
                        'reviewed_at' => now(),
                        'reviewed_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Moment approved')->success()->send();
                    $this->redirect(MomentResource::getUrl('edit', ['record' => $record]));
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => Auth::user()?->can('review', $record) ?? false)
                ->action(function (): void {
                    /** @var Moment $record */
                    $record = $this->getRecord();
                    $record->update([
                        'status' => MomentStatus::Rejected,
                        'reviewed_at' => now(),
                        'reviewed_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Moment rejected')->warning()->send();
                    $this->redirect(MomentResource::getUrl('edit', ['record' => $record]));
                }),
            Action::make('reopen')
                ->label('Reopen to draft')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => Auth::user()?->can('reopen', $record) ?? false)
                ->action(function (): void {
                    /** @var Moment $record */
                    $record = $this->getRecord();
                    $record->update([
                        'status' => MomentStatus::Draft,
                        'submitted_at' => null,
                        'reviewed_at' => null,
                        'reviewed_by' => null,
                    ]);
                    Notification::make()->title('Reopened as draft')->success()->send();
                    $this->redirect(MomentResource::getUrl('edit', ['record' => $record]));
                }),
            DeleteAction::make()
                ->successRedirectUrl(MomentResource::getUrl('index')),
        ];
    }
}
