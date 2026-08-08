<?php

namespace App\Filament\Resources\Recitations\Schemas;

use App\Enums\RecitationStatus;
use App\Models\Recitation;
use App\Models\User;
use App\Support\AudioMetadata;
use App\Support\FilamentR2FileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class RecitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Recitation')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('status_display')
                            ->label('Status')
                            ->content(function (?Recitation $record): string {
                                return $record?->status?->label() ?? RecitationStatus::Draft->label();
                            })
                            ->visible(fn (?Recitation $record): bool => $record !== null),
                        Select::make('reciter_id')
                            ->label('Reciter')
                            ->relationship(
                                name: 'reciter',
                                titleAttribute: 'name_english',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    /** @var User|null $user */
                                    $user = auth()->user();

                                    if ($user?->isProduction() && ! $user->isReviewer()) {
                                        $query->where('created_by', $user->id);
                                    }

                                    return $query->orderBy('name_english');
                                },
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('surah_id')
                            ->label('Surah')
                            ->relationship(
                                name: 'surah',
                                titleAttribute: 'name_english',
                                modifyQueryUsing: fn ($query) => $query->orderBy('number'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => sprintf(
                                    '%d. %s — %s',
                                    $record->number,
                                    $record->name_english,
                                    $record->name_arabic,
                                ),
                            )
                            ->searchable(['number', 'name_english', 'name_arabic', 'name_somali'])
                            ->preload()
                            ->optionsLimit(114)
                            ->required(),
                        FilamentR2FileUpload::configure(
                            FileUpload::make('audio_url')
                                ->label('Audio file')
                                ->directory('recitations/audio')
                                ->acceptedFileTypes([
                                    'audio/mpeg',
                                    'audio/mp3',
                                    'audio/wav',
                                    'audio/x-wav',
                                    'audio/mp4',
                                    'audio/m4a',
                                    'audio/aac',
                                    'audio/ogg',
                                ])
                                ->maxSize(204800)
                                ->required()
                                ->downloadable()
                                ->openable()
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    if (! ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                        && ! (is_array($state) && ($state[0] ?? null) instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                    ) {
                                        return;
                                    }

                                    $meta = AudioMetadata::fromUpload($state, 'r2');

                                    if ($meta['duration'] !== null) {
                                        $set('duration', $meta['duration']);
                                    }

                                    if ($meta['file_size'] !== null) {
                                        $set('file_size', $meta['file_size']);
                                    }
                                })
                                ->columnSpanFull(),
                        ),
                        Hidden::make('duration')->dehydrated(),
                        Hidden::make('file_size')->dehydrated(),
                    ]),
                Section::make('Review notes')
                    ->columnSpanFull()
                    ->visible(fn (?Recitation $record): bool => ($record?->reviewNotes()?->exists()) ?? false)
                    ->schema([
                        ViewField::make('review_notes_panel')
                            ->label('')
                            ->view('filament.forms.review-notes')
                            ->viewData(fn (?Recitation $record): array => [
                                'notes' => $record?->reviewNotes()->with('user')->get() ?? collect(),
                            ])
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
                Section::make('Help listeners follow along')
                    ->description('Mark when each ayah begins in the audio. You can save and finish later.')
                    ->columnSpanFull()
                    ->visible(fn (?Recitation $record): bool => $record !== null)
                    ->schema([
                        ViewField::make('ayah_sync_panel')
                            ->label('')
                            ->view('filament.forms.ayah-sync-panel')
                            ->viewData(fn (?Recitation $record): array => [
                                'record' => $record?->loadMissing(['ayahTimings', 'surah.ayahs']),
                            ])
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
