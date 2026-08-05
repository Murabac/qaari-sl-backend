<?php

namespace App\Filament\Resources\Recitations\Pages;

use App\Enums\RecitationStatus;
use App\Filament\Resources\Recitations\RecitationResource;
use App\Support\AudioMetadata;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateRecitation extends CreateRecord
{
    protected static string $resource = RecitationResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $meta = AudioMetadata::fromUpload($data['audio_url'] ?? null, 'r2');

        $data['duration'] = $meta['duration'] ?? $data['duration'] ?? null;
        $data['file_size'] = $meta['file_size'] ?? $data['file_size'] ?? null;
        $data['created_by'] = Auth::id();
        $data['status'] = RecitationStatus::Draft;

        return $data;
    }
}
