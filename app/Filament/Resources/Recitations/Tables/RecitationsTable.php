<?php

namespace App\Filament\Resources\Recitations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RecitationsTable
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
                TextColumn::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(function (?int $state): string {
                        if ($state === null) {
                            return '—';
                        }

                        return sprintf('%d:%02d', intdiv($state, 60), $state % 60);
                    })
                    ->sortable(),
                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(function (?int $state): string {
                        if ($state === null) {
                            return '—';
                        }

                        if ($state >= 1_048_576) {
                            return number_format($state / 1_048_576, 1).' MB';
                        }

                        return number_format($state / 1024, 1).' KB';
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('reciter_id')
                    ->label('Reciter')
                    ->relationship('reciter', 'name_english')
                    ->searchable()
                    ->preload(),
            ])
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
