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
     * @return array{duration: int|null, file_size: int|null, width: int|null, height: int|null}
     */
    public static function fromUpload(mixed $state, string $disk = 'r2'): array
    {
        $empty = ['duration' => null, 'file_size' => null, 'width' => null, 'height' => null];

        if (blank($state)) {
            return $empty;
        }

        if (is_array($state)) {
            $state = $state[0] ?? null;
        }

        if (blank($state)) {
            return $empty;
        }

        if ($state instanceof TemporaryUploadedFile) {
            return self::fromTemporaryUpload($state, $disk);
        }

        if (! is_string($state)) {
            return $empty;
        }

        if (is_file($state)) {
            return self::metaFromLocalPath($state);
        }

        try {
            if (Storage::disk($disk)->exists($state)) {
                return [
                    'duration' => self::durationFromRemote($disk, $state),
                    'file_size' => Storage::disk($disk)->size($state) ?: null,
                    'width' => null,
                    'height' => null,
                ];
            }
        } catch (Throwable) {
            // Fall through.
        }

        try {
            if (Storage::disk('local')->exists($state)) {
                $localPath = Storage::disk('local')->path($state);
                $meta = self::metaFromLocalPath($localPath);
                $meta['file_size'] = $meta['file_size'] ?? (Storage::disk('local')->size($state) ?: null);

                return $meta;
            }
        } catch (Throwable) {
            // Fall through.
        }

        return $empty;
    }

    /**
     * @return array{duration: int|null, file_size: int|null, width: int|null, height: int|null}
     */
    private static function fromTemporaryUpload(TemporaryUploadedFile $file, string $disk): array
    {
        $fileSize = $file->getSize() ?: null;
        $localPath = $file->getRealPath();

        if (filled($localPath) && is_file($localPath)) {
            $meta = self::metaFromLocalPath($localPath);
            $meta['file_size'] = $fileSize ?? $meta['file_size'];

            return $meta;
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
                        'width' => null,
                        'height' => null,
                    ];
                }
            } catch (Throwable) {
                continue;
            }
        }

        return ['duration' => null, 'file_size' => $fileSize, 'width' => null, 'height' => null];
    }

    private static function durationFromRemote(string $disk, string $path): ?int
    {
        $url = MediaUrl::temporary($disk, $path, minutes: 10);

        if (blank($url)) {
            return null;
        }

        return self::durationWithFfprobe($url);
    }

    /**
     * @return array{duration: int|null, file_size: int|null, width: int|null, height: int|null}
     */
    private static function metaFromLocalPath(string $path): array
    {
        $duration = self::durationWithFfprobe($path);
        $width = null;
        $height = null;
        $fileSize = filesize($path) ?: null;

        $dimensions = self::dimensionsWithFfprobe($path);
        if ($dimensions !== null) {
            [$width, $height] = $dimensions;
        }

        try {
            $analyzer = new getID3;
            $info = $analyzer->analyze($path);

            if ($duration === null) {
                $seconds = data_get($info, 'playtime_seconds');
                if (is_numeric($seconds) && $seconds > 0) {
                    $duration = (int) round((float) $seconds);
                }
            }

            if ($width === null) {
                $resolvedWidth = data_get($info, 'video.resolution_x')
                    ?? data_get($info, 'video.streams.0.resolution_x');
                if (is_numeric($resolvedWidth) && (int) $resolvedWidth > 0) {
                    $width = (int) $resolvedWidth;
                }
            }

            if ($height === null) {
                $resolvedHeight = data_get($info, 'video.resolution_y')
                    ?? data_get($info, 'video.streams.0.resolution_y');
                if (is_numeric($resolvedHeight) && (int) $resolvedHeight > 0) {
                    $height = (int) $resolvedHeight;
                }
            }
        } catch (Throwable) {
            // Keep whatever ffprobe already found.
        }

        return [
            'duration' => $duration,
            'file_size' => $fileSize,
            'width' => $width,
            'height' => $height,
        ];
    }

    private static function durationFromLocalPath(string $path): ?int
    {
        return self::metaFromLocalPath($path)['duration'];
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private static function dimensionsWithFfprobe(string $input): ?array
    {
        $ffprobe = self::ffprobeBinary();

        if ($ffprobe === null) {
            return null;
        }

        try {
            $result = Process::timeout(15)->run([
                $ffprobe,
                '-v', 'error',
                '-select_streams', 'v:0',
                '-show_entries', 'stream=width,height',
                '-of', 'csv=s=x:p=0',
                $input,
            ]);

            if (! $result->successful()) {
                return null;
            }

            $line = trim(explode("\n", trim($result->output()))[0] ?? '');
            if (! preg_match('/^(\d+)x(\d+)$/', $line, $matches)) {
                return null;
            }

            return [(int) $matches[1], (int) $matches[2]];
        } catch (Throwable) {
            return null;
        }
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
