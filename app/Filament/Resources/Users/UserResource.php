<?php

namespace App\Filament\Resources\Users;

use App\Enums\StaffRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 90;

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
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),
                Select::make('staff_role')
                    ->label('Role')
                    ->options(StaffRole::options())
                    ->required()
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(function (?string $state): string {
                        return StaffRole::tryFrom($state ?? '')?->label() ?? ($state ?? '—');
                    }),
                TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordActionsColumnLabel('Actions')
            ->headerActions([
                CreateAction::make()
                    ->successRedirectUrl(UserResource::getUrl('index'))
                    ->using(function (array $data): Model {
                        $role = $data['staff_role'] ?? null;
                        unset($data['staff_role']);

                        /** @var User $user */
                        $user = User::query()->create($data);

                        if (filled($role)) {
                            $user->syncRoles([$role]);
                        }

                        return $user;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->successRedirectUrl(UserResource::getUrl('index'))
                    ->mutateRecordDataUsing(function (array $data, User $record): array {
                        $data['staff_role'] = $record->roles->first()?->name;

                        return $data;
                    })
                    ->using(function (User $record, array $data): User {
                        $role = $data['staff_role'] ?? null;
                        unset($data['staff_role']);

                        $record->update($data);

                        if (filled($role)) {
                            $record->syncRoles([$role]);
                        }

                        return $record;
                    }),
                DeleteAction::make()
                    ->successRedirectUrl(UserResource::getUrl('index')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
