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

        if (str_starts_with($path, 'images/') || str_starts_with($path, '/images/')) {
            return asset(ltrim($path, '/'));
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
