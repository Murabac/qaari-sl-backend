<?php

namespace App\Filament\Resources\Recitations\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class RecitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Recitation')
                    ->columns(2)
                    ->schema([
                        Select::make('reciter_id')
                            ->label('Reciter')
                            ->relationship('reciter', 'name_english')
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
                            ->required(),
                        FileUpload::make('audio_url')
                            ->label('Audio file')
                            ->disk('r2')
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
                            ->visibility('private')
                            ->required()
                            ->downloadable()
                            ->openable()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if (blank($state) || is_array($state)) {
                                    return;
                                }

                                if (Storage::disk('r2')->exists($state)) {
                                    $set('file_size', Storage::disk('r2')->size($state));
                                }
                            })
                            ->columnSpanFull(),
                        TextInput::make('duration')
                            ->label('Duration (seconds)')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Optional for now — can be filled later.'),
                        TextInput::make('file_size')
                            ->label('File size (bytes)')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(),
                    ]),
            ]);
    }
}
