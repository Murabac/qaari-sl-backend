<?php

namespace App\Filament\Resources\Reciters;

use App\Filament\Resources\Reciters\Pages\CreateReciter;
use App\Filament\Resources\Reciters\Pages\EditReciter;
use App\Filament\Resources\Reciters\Pages\ListReciters;
use App\Filament\Resources\Reciters\RelationManagers\RecitationsRelationManager;
use App\Filament\Resources\Reciters\Schemas\ReciterForm;
use App\Filament\Resources\Reciters\Tables\RecitersTable;
use App\Models\Reciter;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ReciterResource extends Resource
{
    protected static ?string $model = Reciter::class;

    protected static ?string $recordTitleAttribute = 'name_english';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ReciterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecitersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = auth()->user();

        $query = parent::getEloquentQuery();

        if ($user?->isProduction() && ! $user->isReviewer()) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            RecitationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReciters::route('/'),
            'create' => CreateReciter::route('/create'),
            'edit' => EditReciter::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', Reciter::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', Reciter::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update', $record) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete', $record) ?? false;
    }
}
