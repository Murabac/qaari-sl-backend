<?php

namespace App\Filament\Resources\Reciters;

use App\Filament\Resources\Reciters\Pages\CreateReciter;
use App\Filament\Resources\Reciters\Pages\EditReciter;
use App\Filament\Resources\Reciters\Pages\ListReciters;
use App\Filament\Resources\Reciters\Schemas\ReciterForm;
use App\Filament\Resources\Reciters\Tables\RecitersTable;
use App\Models\Reciter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReciters::route('/'),
            'create' => CreateReciter::route('/create'),
            'edit' => EditReciter::route('/{record}/edit'),
        ];
    }
}
