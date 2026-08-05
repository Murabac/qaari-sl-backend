<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaUrl
{
    public static function temporary(string $disk, ?string $path, int $minutes = 30): ?string
    {
        if (blank($path)) {
            return null;
        }

        try {
            return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($minutes));
        } catch (Throwable) {
            try {
                return Storage::disk($disk)->url($path);
            } catch (Throwable) {
                return null;
            }
        }
    }
}
