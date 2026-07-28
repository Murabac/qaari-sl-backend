<?php

namespace App\Support;

use getID3;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AudioMetadata
{
    /**
     * @return array{duration: int|null, file_size: int|null}
     */
    public static function fromUpload(mixed $state, string $disk = 'r2'): array
    {
        if (blank($state)) {
            return ['duration' => null, 'file_size' => null];
        }

        if (is_array($state)) {
            $state = $state[0] ?? null;
        }

        if (blank($state)) {
            return ['duration' => null, 'file_size' => null];
        }

        $localPath = null;
        $cleanup = false;
        $fileSize = null;

        if ($state instanceof TemporaryUploadedFile) {
            $localPath = $state->getRealPath();
            $fileSize = $state->getSize() ?: null;
        } elseif (is_string($state)) {
            if (is_file($state)) {
                $localPath = $state;
                $fileSize = filesize($state) ?: null;
            } elseif (Storage::disk($disk)->exists($state)) {
                $fileSize = Storage::disk($disk)->size($state);
                $localPath = tempnam(sys_get_temp_dir(), 'qaari-audio-');
                file_put_contents($localPath, Storage::disk($disk)->get($state));
                $cleanup = true;
            } elseif (Storage::disk('local')->exists($state)) {
                $localPath = Storage::disk('local')->path($state);
                $fileSize = Storage::disk('local')->size($state);
            }
        }

        $duration = null;

        if (filled($localPath) && is_file($localPath)) {
            $analyzer = new getID3;
            $info = $analyzer->analyze($localPath);
            $seconds = data_get($info, 'playtime_seconds');

            if (is_numeric($seconds) && $seconds > 0) {
                $duration = (int) round((float) $seconds);
            }

            if ($fileSize === null) {
                $fileSize = filesize($localPath) ?: null;
            }
        }

        if ($cleanup && filled($localPath) && is_file($localPath)) {
            @unlink($localPath);
        }

        return [
            'duration' => $duration,
            'file_size' => $fileSize,
        ];
    }
}
