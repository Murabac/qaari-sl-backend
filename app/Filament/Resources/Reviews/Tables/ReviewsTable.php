<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Enums\RecitationStatus;
use App\Models\Recitation;
use App\Models\RecitationReviewNote;
use App\Support\AudioMetadata;
use App\Support\MediaUrl;
use App\Support\VoiceNote;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reciter.name_english')
                    ->label('Reciter')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('surah.number')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('surah.name_english')
                    ->label('Surah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Uploaded by')
                    ->toggleable(),
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (RecitationStatus $state): string => $state->label())
                    ->color(fn (RecitationStatus $state): string => $state->color()),
            ])
            ->defaultSort('submitted_at')
            ->paginated([10, 25, 50])
            ->recordActionsColumnLabel('Actions')
            ->recordActions([
                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-musical-note')
                    ->color('primary')
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(fn (Recitation $record): string => sprintf(
                        'Review · %d. %s',
                        $record->surah?->number ?? 0,
                        $record->surah?->name_english ?? 'Surah',
                    ))
                    ->modalDescription(new HtmlString(
                        'Listen in place — play, pause, and scrub — then approve or reject.'
                    ))
                    ->modalContent(fn (Recitation $record) => view('filament.reviews.review-player', [
                        'record' => $record->loadMissing(['reciter', 'surah', 'creator']),
                        'audioUrl' => MediaUrl::temporary('r2', $record->audio_url),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->extraModalFooterActions([
                        Action::make('approve')
                            ->label('Approve')
                            ->color('success')
                            ->icon('heroicon-o-check-circle')
                            ->requiresConfirmation()
                            ->cancelParentActions()
                            ->action(function (Recitation $record): void {
                                $record->update([
                                    'status' => RecitationStatus::Approved,
                                    'reviewed_at' => now(),
                                    'reviewed_by' => Auth::id(),
                                ]);

                                Notification::make()
                                    ->title('Recitation approved')
                                    ->success()
                                    ->send();
                            }),
                        Action::make('reject')
                            ->label('Reject')
                            ->color('danger')
                            ->icon('heroicon-o-x-circle')
                            ->modalHeading('Reject with voice note')
                            ->modalDescription('Record or upload feedback. Production must re-upload the full surah.')
                            ->form([
                                ViewField::make('recording')
                                    ->label('Record with your microphone')
                                    ->view('filament.forms.voice-recorder'),
                                FileUpload::make('voice_note')
                                    ->label('Or upload an audio file')
                                    ->disk('r2')
                                    ->visibility('private')
                                    ->directory('reviews/voice-notes')
                                    ->acceptedFileTypes([
                                        'audio/mpeg',
                                        'audio/mp3',
                                        'audio/wav',
                                        'audio/webm',
                                        'audio/mp4',
                                        'audio/m4a',
                                        'audio/ogg',
                                        'audio/x-m4a',
                                    ])
                                    ->maxSize(20480),
                                TextInput::make('caption')
                                    ->label('Short caption (optional)')
                                    ->maxLength(255),
                            ])
                            ->cancelParentActions()
                            ->action(function (Recitation $record, array $data): void {
                                $recorded = VoiceNote::storeRecording($data['recording'] ?? null);

                                if ($recorded !== null) {
                                    $path = $recorded['path'];
                                    $meta = [
                                        'duration' => $recorded['duration'],
                                        'file_size' => $recorded['file_size'],
                                    ];
                                } else {
                                    $path = $data['voice_note'] ?? null;

                                    if (blank($path)) {
                                        Notification::make()
                                            ->title('Voice note required')
                                            ->body('Record feedback or upload an audio file before rejecting.')
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    $meta = AudioMetadata::fromUpload($path, 'r2');
                                }

                                RecitationReviewNote::query()->create([
                                    'recitation_id' => $record->id,
                                    'user_id' => Auth::id(),
                                    'audio_url' => $path,
                                    'duration' => $meta['duration'],
                                    'file_size' => $meta['file_size'],
                                    'caption' => $data['caption'] ?? null,
                                    'status_at_time' => RecitationStatus::Rejected,
                                ]);

                                $record->update([
                                    'status' => RecitationStatus::Rejected,
                                    'reviewed_at' => now(),
                                    'reviewed_by' => Auth::id(),
                                ]);

                                Notification::make()
                                    ->title('Recitation rejected')
                                    ->body('Production can re-upload the corrected audio and resubmit.')
                                    ->danger()
                                    ->send();
                            }),
                    ]),
            ]);
    }
}
