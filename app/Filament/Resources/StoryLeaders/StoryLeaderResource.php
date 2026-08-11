<?php

namespace App\Filament\Resources\StoryLeaders;

use App\Enums\StoryLeaderTier;
use App\Filament\Resources\StoryLeaders\Pages\ManageStoryLeaders;
use App\Models\StoryLeader;
use App\Support\MediaUrl;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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

class StoryLeaderResource extends Resource
{
    protected static ?string $model = StoryLeader::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Story Page';

    protected static ?string $navigationLabel = 'Patrons & Leadership';

    protected static ?string $modelLabel = 'leader';

    protected static ?string $pluralModelLabel = 'patrons & leadership';

    protected static ?int $navigationSort = 71;

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
                TextInput::make('title')
                    ->label('Title / role')
                    ->required()
                    ->maxLength(255),
                Select::make('tier')
                    ->options(StoryLeaderTier::options())
                    ->required()
                    ->native(false),
                TextInput::make('sort_order')
                    ->label('Display order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                FileUpload::make('photo_url')
                    ->label('Photo')
                    ->disk('r2')
                    ->visibility('private')
                    ->directory('story/leaders')
                    ->image()
                    ->avatar()
                    ->maxSize(5120)
                    ->fetchFileInformation(false)
                    ->getUploadedFileUsing(function (BaseFileUpload $component, string $file, string|array|null $storedFileNames): ?array {
                        $url = MediaUrl::temporary('r2', $file);

                        if (blank($url)) {
                            return null;
                        }

                        return [
                            'name' => is_array($storedFileNames) ? ($storedFileNames[$file] ?? basename($file)) : ($storedFileNames ?: basename($file)),
                            'size' => 0,
                            'type' => null,
                            'url' => $url,
                        ];
                    })
                    ->columnSpanFull(),
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
                    ->getStateUsing(fn (StoryLeader $record): ?string => MediaUrl::temporary('r2', $record->photo_url))
                    ->defaultImageUrl(asset('images/logo.svg')),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('title')->limit(40),
                TextColumn::make('tier')
                    ->badge()
                    ->formatStateUsing(fn (StoryLeaderTier $state): string => $state->label()),
                TextColumn::make('sort_order')->label('Order')->sortable(),
                ToggleColumn::make('is_active')->label('Visible'),
            ])
            ->defaultSort('sort_order')
            ->recordActionsColumnLabel('Actions')
            ->recordActions([
                EditAction::make()
                    ->successRedirectUrl(StoryLeaderResource::getUrl('index')),
                DeleteAction::make()
                    ->successRedirectUrl(StoryLeaderResource::getUrl('index')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->successRedirectUrl(StoryLeaderResource::getUrl('index')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStoryLeaders::route('/'),
        ];
    }
}
