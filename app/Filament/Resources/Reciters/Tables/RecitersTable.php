<?php

namespace App\Filament\Resources\Reciters\Tables;

use App\Models\Reciter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->filters([
                SelectFilter::make('region')
                    ->label('Region')
                    ->options(fn (): array => Reciter::query()
                        ->whereNotNull('region')
                        ->where('region', '!=', '')
                        ->distinct()
                        ->orderBy('region')
                        ->pluck('region', 'region')
                        ->all())
                    ->searchable(),
                TernaryFilter::make('has_photo')
                    ->label('Photo')
                    ->placeholder('All')
                    ->trueLabel('Has photo')
                    ->falseLabel('No photo')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('photo_url')->where('photo_url', '!=', ''),
                        false: fn (Builder $query) => $query->where(function (Builder $query): void {
                            $query->whereNull('photo_url')->orWhere('photo_url', '');
                        }),
                    ),
                TernaryFilter::make('has_recitations')
                    ->label('Recitations')
                    ->placeholder('All')
                    ->trueLabel('Has recitations')
                    ->falseLabel('No recitations')
                    ->queries(
                        true: fn (Builder $query) => $query->has('recitations'),
                        false: fn (Builder $query) => $query->doesntHave('recitations'),
                    ),
            ], layout: FiltersLayout::AboveContentCollapsible)
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
