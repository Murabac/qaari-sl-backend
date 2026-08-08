<?php

namespace App\Support;

use getID3;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

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

        if ($state instanceof TemporaryUploadedFile) {
            return self::fromTemporaryUpload($state, $disk);
        }

        if (! is_string($state)) {
            return ['duration' => null, 'file_size' => null];
        }

        if (is_file($state)) {
            return [
                'duration' => self::durationFromLocalPath($state),
                'file_size' => filesize($state) ?: null,
            ];
        }

        try {
            if (Storage::disk($disk)->exists($state)) {
                return [
                    'duration' => self::durationFromRemote($disk, $state),
                    'file_size' => Storage::disk($disk)->size($state) ?: null,
                ];
            }
        } catch (Throwable) {
            // Fall through.
        }

        try {
            if (Storage::disk('local')->exists($state)) {
                $localPath = Storage::disk('local')->path($state);

                return [
                    'duration' => self::durationFromLocalPath($localPath),
                    'file_size' => Storage::disk('local')->size($state) ?: null,
                ];
            }
        } catch (Throwable) {
            // Fall through.
        }

        return ['duration' => null, 'file_size' => null];
    }

    /**
     * @return array{duration: int|null, file_size: int|null}
     */
    private static function fromTemporaryUpload(TemporaryUploadedFile $file, string $disk): array
    {
        $fileSize = $file->getSize() ?: null;
        $localPath = $file->getRealPath();

        if (filled($localPath) && is_file($localPath)) {
            return [
                'duration' => self::durationFromLocalPath($localPath),
                'file_size' => $fileSize,
            ];
        }

        // S3/R2 temporary uploads: prefer ffprobe on a signed URL (no full download).
        $key = $file->getFilename();
        $path = method_exists($file, 'getPathname') ? $file->getPathname() : null;

        foreach (array_filter([$path, $key, 'livewire-tmp/'.$key]) as $candidate) {
            try {
                if (is_string($candidate) && Storage::disk($disk)->exists($candidate)) {
                    return [
                        'duration' => self::durationFromRemote($disk, $candidate),
                        'file_size' => $fileSize ?? Storage::disk($disk)->size($candidate) ?: null,
                    ];
                }
            } catch (Throwable) {
                continue;
            }
        }

        return ['duration' => null, 'file_size' => $fileSize];
    }

    private static function durationFromRemote(string $disk, string $path): ?int
    {
        $url = MediaUrl::temporary($disk, $path, minutes: 10);

        if (blank($url)) {
            return null;
        }

        return self::durationWithFfprobe($url);
    }

    private static function durationFromLocalPath(string $path): ?int
    {
        $fromProbe = self::durationWithFfprobe($path);

        if ($fromProbe !== null) {
            return $fromProbe;
        }

        try {
            $analyzer = new getID3;
            $info = $analyzer->analyze($path);
            $seconds = data_get($info, 'playtime_seconds');

            if (is_numeric($seconds) && $seconds > 0) {
                return (int) round((float) $seconds);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private static function durationWithFfprobe(string $input): ?int
    {
        $ffprobe = self::ffprobeBinary();

        if ($ffprobe === null) {
            return null;
        }

        try {
            $result = Process::timeout(15)->run([
                $ffprobe,
                '-v', 'error',
                '-show_entries', 'format=duration',
                '-of', 'default=noprint_wrappers=1:nokey=1',
                $input,
            ]);

            if (! $result->successful()) {
                return null;
            }

            $seconds = trim($result->output());

            if (is_numeric($seconds) && (float) $seconds > 0) {
                return (int) round((float) $seconds);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private static function ffprobeBinary(): ?string
    {
        $candidates = [
            base_path('node_modules/ffprobe-static/bin/win32/x64/ffprobe.exe'),
            base_path('node_modules/ffprobe-static/ffprobe'),
            base_path('tools/bin/ffprobe.exe'),
            base_path('tools/bin/ffprobe'),
        ];

        foreach (glob(base_path('node_modules/ffprobe-static/**/ffprobe.exe')) ?: [] as $match) {
            array_unshift($candidates, $match);
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        // Only use PATH ffprobe when it actually exists — otherwise Process::run
        // can hang/timeout on Coolify and nginx returns 500 after the record saved.
        try {
            $result = Process::timeout(3)->run([
                PHP_OS_FAMILY === 'Windows' ? 'where' : 'which',
                'ffprobe',
            ]);

            if ($result->successful() && filled(trim($result->output()))) {
                return 'ffprobe';
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
