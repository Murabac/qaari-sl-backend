<?php

namespace App\Filament\Resources\Moments\Schemas;

use App\Enums\MomentStatus;
use App\Models\Moment;
use App\Models\User;
use App\Support\FilamentR2FileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MomentForm
{
    public static function configure(Schema $schema): Schema
    {
        $maxKb = (int) config('moments.max_upload_kb', 81920);

        return $schema
            ->columns(1)
            ->components([
                Section::make('Moment')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('status_display')
                            ->label('Status')
                            ->content(fn (?Moment $record): string => $record?->status?->label() ?? MomentStatus::Draft->label())
                            ->visible(fn (?Moment $record): bool => $record !== null),
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
                            ->label('Surah (optional)')
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
                            ->nullable(),
                        TextInput::make('ayah_number')
                            ->label('Ayah (optional)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(286)
                            ->nullable(),
                        TextInput::make('title')
                            ->label('Title')
                            ->maxLength(120)
                            ->columnSpanFull(),
                        Textarea::make('caption')
                            ->label('Caption')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        FilamentR2FileUpload::configure(
                            FileUpload::make('video_url')
                                ->label('Video (MP4)')
                                ->directory(config('moments.video_directory', 'moments/videos'))
                                ->acceptedFileTypes(['video/mp4', 'video/quicktime'])
                                ->maxSize($maxKb)
                                ->required()
                                ->helperText('Prefer 9:16 H.264/AAC MP4, max '.config('moments.max_duration_seconds', 60).' seconds.')
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    $file = $state;
                                    if (is_array($state)) {
                                        $file = $state[0] ?? null;
                                    }
                                    if ($file instanceof TemporaryUploadedFile) {
                                        $set('file_size', $file->getSize());
                                    }
                                }),
                        ),
                        FilamentR2FileUpload::configure(
                            FileUpload::make('poster_url')
                                ->label('Poster image (optional)')
                                ->directory(config('moments.poster_directory', 'moments/posters'))
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(5120)
                                ->image(),
                        ),
                        TextInput::make('duration')
                            ->label('Duration (seconds)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue((int) config('moments.max_duration_seconds', 60))
                            ->helperText('Enter clip length in seconds (max '.config('moments.max_duration_seconds', 60).').')
                            ->required(),
                        TextInput::make('file_size')
                            ->numeric()
                            ->hidden(),
                        TextInput::make('width')
                            ->label('Width (px)')
                            ->numeric()
                            ->nullable(),
                        TextInput::make('height')
                            ->label('Height (px)')
                            ->numeric()
                            ->nullable(),
                    ]),
            ]);
    }
}
