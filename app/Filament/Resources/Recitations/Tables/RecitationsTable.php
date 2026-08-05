<?php

namespace App\Filament\Resources\Recitations\Tables;

use App\Enums\RecitationStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

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
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (RecitationStatus $state): string => $state->label())
                    ->color(fn (RecitationStatus $state): string => $state->color())
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading('Choose a reciter')
            ->modifyQueryUsing(function (Builder $query, Component $livewire): Builder {
                $reciterId = $livewire->getTableFilterState('reciter_id')['value'] ?? null;

                if (blank($reciterId)) {
                    return $query->whereRaw('0 = 1');
                }

                return $query;
            })
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->extremePaginationLinks()
            ->deferLoading()
            ->filters([
                SelectFilter::make('reciter_id')
                    ->label('Reciter')
                    ->relationship('reciter', 'name_english')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('surah_id')
                    ->label('Surah')
                    ->relationship(
                        name: 'surah',
                        titleAttribute: 'name_english',
                        modifyQueryUsing: fn ($query) => $query->orderBy('number'),
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn ($record) => sprintf('%d. %s', $record->number, $record->name_english),
                    )
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(RecitationStatus::options()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->recordActionsColumnLabel('Actions')
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
