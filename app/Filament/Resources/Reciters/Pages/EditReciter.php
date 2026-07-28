<?php

namespace App\Filament\Resources\Reciters\Pages;

use App\Filament\Resources\Reciters\ReciterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReciter extends EditRecord
{
    protected static string $resource = ReciterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
