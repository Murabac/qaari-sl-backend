<?php

namespace App\Filament\Resources\Moments\Pages;

use App\Enums\MomentStatus;
use App\Filament\Resources\Moments\MomentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMoment extends CreateRecord
{
    protected static string $resource = MomentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (is_array($data['video_url'] ?? null)) {
            $data['video_url'] = $data['video_url'][0] ?? null;
        }
        if (is_array($data['poster_url'] ?? null)) {
            $data['poster_url'] = $data['poster_url'][0] ?? null;
        }

        $data['created_by'] = Auth::id();
        $data['status'] = MomentStatus::Draft;
        $data['likes_count'] = 0;

        $maxDuration = (int) config('moments.max_duration_seconds', 60);
        if (isset($data['duration'])) {
            $data['duration'] = min((int) $data['duration'], $maxDuration);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('edit', ['record' => $this->getRecord()]);
    }
}
