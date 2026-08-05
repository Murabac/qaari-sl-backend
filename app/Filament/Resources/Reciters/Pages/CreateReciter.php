<?php

namespace App\Filament\Resources\Reciters\Pages;

use App\Filament\Resources\Reciters\ReciterResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateReciter extends CreateRecord
{
    protected static string $resource = ReciterResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
