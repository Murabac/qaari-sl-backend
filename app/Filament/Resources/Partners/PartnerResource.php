<?php

namespace App\Filament\Resources\Partners;

use App\Filament\Resources\Partners\Pages\ManagePartners;
use App\Models\Partner;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Story Page';

    protected static ?string $navigationLabel = 'Partners';

    protected static ?string $modelLabel = 'partner';

    protected static ?string $pluralModelLabel = 'partners';

    protected static ?int $navigationSort = 73;

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('url')
                    ->label('Website URL')
                    ->url()
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->label('Display order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                FileUpload::make('logo_url')
                    ->label('Logo')
                    ->disk('r2')
                    ->directory('partners/logos')
                    ->image()
                    ->imageEditor()
                    ->maxSize(5120)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Visible on homepage')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->disk('r2'),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('url')->limit(40)->toggleable(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
                ToggleColumn::make('is_active')->label('Visible'),
            ])
            ->defaultSort('sort_order')
            ->recordActionsColumnLabel('Actions')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePartners::route('/'),
        ];
    }
}
