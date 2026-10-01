<?php

namespace App\Filament\Resources\Moments;

use App\Filament\Resources\Moments\Pages\CreateMoment;
use App\Filament\Resources\Moments\Pages\EditMoment;
use App\Filament\Resources\Moments\Pages\ListMoments;
use App\Filament\Resources\Moments\Schemas\MomentForm;
use App\Filament\Resources\Moments\Tables\MomentsTable;
use App\Models\Moment;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MomentResource extends Resource
{
    protected static ?string $model = Moment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Moments';

    protected static ?string $modelLabel = 'Moment';

    protected static ?string $pluralModelLabel = 'Moments';

    public static function form(Schema $schema): Schema
    {
        return MomentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MomentsTable::configure($table);
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMoments::route('/'),
            'create' => CreateMoment::route('/create'),
            'edit' => EditMoment::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', Moment::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', Moment::class) ?? false;
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
