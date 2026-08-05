<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VoiceNote
{
    /**
     * Store a browser recording (data URL) on the given disk.
     *
     * @param  array<string, mixed>|null  $recording
     * @return array{path: string, duration: int|null, file_size: int}|null
     */
    public static function storeRecording(?array $recording, string $disk = 'r2', string $directory = 'reviews/voice-notes'): ?array
    {
        $dataUrl = $recording['data'] ?? null;

        if (! is_string($dataUrl) || ! str_contains($dataUrl, ',')) {
            return null;
        }

        [$header, $encoded] = explode(',', $dataUrl, 2);
        $binary = base64_decode($encoded, true);

        if ($binary === false || $binary === '') {
            return null;
        }

        $extension = str_contains($header, 'mp4') || str_contains($header, 'm4a')
            ? 'm4a'
            : (str_contains($header, 'ogg') ? 'ogg' : 'webm');

        $path = trim($directory, '/').'/'.Str::ulid().'.'.$extension;

        Storage::disk($disk)->put($path, $binary);

        $duration = $recording['duration'] ?? null;

        return [
            'path' => $path,
            'duration' => is_numeric($duration) && $duration > 0 ? (int) round($duration) : null,
            'file_size' => strlen($binary),
        ];
    }
}
