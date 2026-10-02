<?php

namespace App\Filament\Resources\Moments\Schemas;

use App\Enums\MomentStatus;
use App\Models\Moment;
use App\Models\User;
use App\Support\AudioMetadata;
use App\Support\FilamentR2FileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
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
        $maxDuration = (int) config('moments.max_duration_seconds', 60);

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
                            ->label('Reciter (optional)')
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
                            ->nullable(),
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
                                ->helperText('Prefer 9:16 H.264/AAC MP4, max '.$maxDuration.' seconds. Duration is detected automatically.')
                                ->afterStateUpdated(function ($state, callable $set) use ($maxDuration): void {
                                    if (! ($state instanceof TemporaryUploadedFile)
                                        && ! (is_array($state) && ($state[0] ?? null) instanceof TemporaryUploadedFile)
                                    ) {
                                        return;
                                    }

                                    $meta = AudioMetadata::fromUpload($state, 'r2');

                                    if ($meta['duration'] !== null) {
                                        $set('duration', min($meta['duration'], $maxDuration));
                                    }

                                    if ($meta['file_size'] !== null) {
                                        $set('file_size', $meta['file_size']);
                                    }

                                    if ($meta['width'] !== null) {
                                        $set('width', $meta['width']);
                                    }

                                    if ($meta['height'] !== null) {
                                        $set('height', $meta['height']);
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
                            ->maxValue($maxDuration)
                            ->helperText('Detected from the uploaded video (max '.$maxDuration.'s). Edit only if detection fails.')
                            ->required(),
                        Hidden::make('file_size')->dehydrated(),
                        TextInput::make('width')
                            ->label('Width (px)')
                            ->numeric()
                            ->nullable()
                            ->helperText('Detected from video when available.'),
                        TextInput::make('height')
                            ->label('Height (px)')
                            ->numeric()
                            ->nullable(),
                    ]),
            ]);
    }
}
