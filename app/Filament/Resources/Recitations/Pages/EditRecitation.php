<?php

namespace App\Filament\Resources\Recitations\Pages;

use App\Filament\Resources\Recitations\RecitationResource;
use App\Support\AudioMetadata;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRecitation extends EditRecord
{
    protected static string $resource = RecitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $meta = AudioMetadata::fromUpload($data['audio_url'] ?? null, 'r2');

        if ($meta['duration'] !== null) {
            $data['duration'] = $meta['duration'];
        }

        if ($meta['file_size'] !== null) {
            $data['file_size'] = $meta['file_size'];
        }

        return $data;
    }
}
