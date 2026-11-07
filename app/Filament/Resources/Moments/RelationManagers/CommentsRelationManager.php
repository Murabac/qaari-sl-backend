<?php

namespace App\Filament\Resources\Moments\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Comments';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Listener'),
                TextColumn::make('body')->wrap(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}
