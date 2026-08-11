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
                try {
                    // Never call Storage::size()/mimeType()/exists() here — those
                    // HEAD requests against private R2 are what 500 Coolify remorphs.
                    $url = MediaUrl::temporary('r2', $file);

                    $name = is_array($storedFileNames)
                        ? ($storedFileNames[$file] ?? basename($file))
                        : ($storedFileNames ?: basename($file));

                    return [
                        'name' => $name,
                        'size' => 0,
                        'type' => null,
                        'url' => $url,
                    ];
                } catch (\Throwable) {
                    return [
                        'name' => basename($file),
                        'size' => 0,
                        'type' => null,
                        'url' => null,
                    ];
                }
            });
    }
}
