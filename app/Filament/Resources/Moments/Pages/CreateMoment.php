<?php

namespace App\Filament\Resources\Moments\Pages;

use App\Enums\MomentStatus;
use App\Filament\Resources\Moments\MomentResource;
use App\Support\AudioMetadata;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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

        return self::fillVideoMetadata($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fillVideoMetadata(array $data): array
    {
        $maxDuration = (int) config('moments.max_duration_seconds', 60);
        $video = $data['video_url'] ?? null;

        $needsProbe = blank($data['duration'] ?? null)
            || blank($data['file_size'] ?? null)
            || blank($data['width'] ?? null)
            || blank($data['height'] ?? null);

        if ($needsProbe && ($video instanceof TemporaryUploadedFile || is_string($video))) {
            $meta = AudioMetadata::fromUpload($video, 'r2');

            if (blank($data['duration'] ?? null) && $meta['duration'] !== null) {
                $data['duration'] = $meta['duration'];
            }
            if (blank($data['file_size'] ?? null) && $meta['file_size'] !== null) {
                $data['file_size'] = $meta['file_size'];
            }
            if (blank($data['width'] ?? null) && $meta['width'] !== null) {
                $data['width'] = $meta['width'];
            }
            if (blank($data['height'] ?? null) && $meta['height'] !== null) {
                $data['height'] = $meta['height'];
            }
        }

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
