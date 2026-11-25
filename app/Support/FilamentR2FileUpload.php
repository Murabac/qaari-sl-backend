<?php

namespace App\Support;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;

class FilamentR2FileUpload
{
    /**
     * Private R2 objects cannot be probed with size/mime HEAD requests the way
     * local disks can. Filament's default getUploadedFile() will 500 when those
     * calls throw (common right after create, when redirecting to edit).
     *
     * The browser must also not fetch the signed R2 URL. FilePond shows
     * "Loading… Waiting for size" until that request returns a length, and a
     * private bucket does not allow the admin origin to read it. A completed
     * file descriptor (name, non-zero size, and a mime that matches the field)
     * makes FilePond skip the load and show the remove button immediately.
     */
    public static function configure(FileUpload $upload): FileUpload
    {
        return $upload
            ->disk('r2')
            ->visibility('private')
            ->fetchFileInformation(false)
            ->previewable(false)
            ->openable(false)
            ->downloadable(false)
            ->getUploadedFileUsing(function (BaseFileUpload $component, string $file, string|array|null $storedFileNames): ?array {
                $name = is_array($storedFileNames)
                    ? ($storedFileNames[$file] ?? basename($file))
                    : ($storedFileNames ?: basename($file));

                return [
                    'name' => $name,
                    'size' => 1,
                    'type' => self::placeholderType($component, $file),
                    // A stored path, not a signed R2 URL. FilePond uses this only
                    // as the item id once name/size/type are present.
                    'url' => $file,
                ];
            });
    }

    private static function placeholderType(BaseFileUpload $component, string $file): string
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $guess = match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            'aac' => 'audio/aac',
            'ogg' => 'audio/ogg',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
            default => 'application/octet-stream',
        };

        $accepted = $component->getAcceptedFileTypes() ?? [];

        if ($accepted === [] || in_array($guess, $accepted, true)) {
            return $guess;
        }

        foreach ($accepted as $type) {
            if (str_ends_with($type, '/*') && str_starts_with($guess, substr($type, 0, -1))) {
                return $guess;
            }
        }

        foreach ($accepted as $type) {
            if ($extension !== '' && str_contains($type, $extension)) {
                return $type;
            }
        }

        return $guess;
    }
}
