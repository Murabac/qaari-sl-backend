<?php

namespace App\Filament\Resources\Reciters\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReciterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Names')
                    ->schema([
                        TextInput::make('name_somali')
                            ->label('Somali')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('name_arabic')
                            ->label('Arabic')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'rtl'])
                            ->columnSpanFull(),
                        TextInput::make('name_english')
                            ->label('English')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Bios')
                    ->schema([
                        Textarea::make('bio_somali')
                            ->label('Bio (Somali)')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('bio_arabic')
                            ->label('Bio (Arabic)')
                            ->rows(4)
                            ->extraInputAttributes(['dir' => 'rtl'])
                            ->columnSpanFull(),
                        Textarea::make('bio_english')
                            ->label('Bio (English)')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
                Section::make('Profile')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('photo_url')
                            ->label('Photo')
                            ->disk('r2')
                            ->directory('reciters/photos')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->columnSpanFull(),
                        TextInput::make('region')
                            ->label('Region')
                            ->placeholder('e.g. Hargeisa')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
