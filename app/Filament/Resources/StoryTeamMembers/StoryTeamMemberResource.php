<?php

namespace App\Filament\Resources\StoryTeamMembers;

use App\Filament\Resources\StoryTeamMembers\Pages\ManageStoryTeamMembers;
use App\Models\StoryTeamMember;
use App\Support\FilamentR2FileUpload;
use App\Support\MediaUrl;
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

class StoryTeamMemberResource extends Resource
{
    protected static ?string $model = StoryTeamMember::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Story Page';

    protected static ?string $navigationLabel = 'Behind the Voices';

    protected static ?string $modelLabel = 'team member';

    protected static ?string $pluralModelLabel = 'team members';

    protected static ?int $navigationSort = 72;

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
                TextInput::make('role')
                    ->required()
                    ->maxLength(255),
                TextInput::make('description')
                    ->label('One-line description')
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->label('Display order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                FilamentR2FileUpload::configure(
                    FileUpload::make('photo_url')
                        ->label('Photo')
                        ->directory('story/team')
                        ->image()
                        ->avatar()
                        ->maxSize(5120)
                        ->columnSpanFull(),
                ),
                Toggle::make('is_active')
                    ->label('Visible on site')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_url')
                    ->label('Photo')
                    ->circular()
                    ->checkFileExistence(false)
                    ->getStateUsing(fn (StoryTeamMember $record): ?string => MediaUrl::temporary('r2', $record->photo_url))
                    ->defaultImageUrl(asset('images/logo.svg')),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('role')->limit(40),
                TextColumn::make('description')->limit(50)->toggleable(),
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
            'index' => ManageStoryTeamMembers::route('/'),
        ];
    }
}
