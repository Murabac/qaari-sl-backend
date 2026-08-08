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
        // Prefer duration/size already set by the upload afterStateUpdated hook.
        // Only probe storage when those are missing — probing R2 on Coolify is slow
        // and used to blow past nginx's default 60s fastcgi timeout after save.
        if (blank($data['duration'] ?? null) || blank($data['file_size'] ?? null)) {
            $meta = AudioMetadata::fromUpload($data['audio_url'] ?? null, 'r2');
            $data['duration'] = $data['duration'] ?? $meta['duration'];
            $data['file_size'] = $data['file_size'] ?? $meta['file_size'];
        }

        $data['created_by'] = Auth::id();
        $data['status'] = RecitationStatus::Draft;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Land on the list (fast) instead of edit (R2 signed URLs + ayah panel).
        return $this->getResourceUrl('index');
    }
}
