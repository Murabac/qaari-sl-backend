<?php

namespace App\Filament\Resources\Reciters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RecitersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_url')
                    ->label('Photo')
                    ->disk('r2')
                    ->circular()
                    ->defaultImageUrl(asset('images/logo.svg')),
                TextColumn::make('name_english')
                    ->label('English')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_somali')
                    ->label('Somali')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name_arabic')
                    ->label('Arabic')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('region')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('recitations_count')
                    ->counts('recitations')
                    ->label('Recitations'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name_english')
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
