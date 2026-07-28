<?php

namespace App\Filament\Resources\Recitations;

use App\Filament\Resources\Recitations\Pages\CreateRecitation;
use App\Filament\Resources\Recitations\Pages\EditRecitation;
use App\Filament\Resources\Recitations\Pages\ListRecitations;
use App\Filament\Resources\Recitations\Schemas\RecitationForm;
use App\Filament\Resources\Recitations\Tables\RecitationsTable;
use App\Models\Recitation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RecitationResource extends Resource
{
    protected static ?string $model = Recitation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMusicalNote;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return RecitationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecitationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecitations::route('/'),
            'create' => CreateRecitation::route('/create'),
            'edit' => EditRecitation::route('/{record}/edit'),
        ];
    }
}
