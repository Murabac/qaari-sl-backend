<?php

namespace App\Filament\Resources\Moments\Tables;

use App\Enums\MomentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MomentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reciter.name_english')
                    ->label('Reciter')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('title')
                    ->searchable()
                    ->limit(40)
                    ->placeholder('—'),
                TextColumn::make('surah.number')
                    ->label('Surah #')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('ayah_number')
                    ->label('Ayah')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (MomentStatus $state): string => $state->label())
                    ->color(fn (MomentStatus $state): string => $state->color())
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
                TextColumn::make('likes_count')
                    ->label('Likes')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading('No moments yet')
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('reciter_id')
                    ->label('Reciter')
                    ->relationship('reciter', 'name_english')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(MomentStatus::options()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }
}
