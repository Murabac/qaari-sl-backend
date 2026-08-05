<?php

namespace App\Support;

use App\Models\Reciter;
use App\Models\Surah;

class LocaleText
{
    public static function locale(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, ['en', 'so', 'ar'], true) ? $locale : 'en';
    }

    public static function isRtl(): bool
    {
        return self::locale() === 'ar';
    }

    public static function reciterName(Reciter $reciter): string
    {
        return self::pick($reciter, 'name') ?? '';
    }

    public static function reciterBio(Reciter $reciter): ?string
    {
        return self::pick($reciter, 'bio');
    }

    public static function surahName(Surah $surah): string
    {
        return self::pick($surah, 'name') ?? '';
    }

    /**
     * @param  object{name_english?: ?string, name_somali?: ?string, name_arabic?: ?string, bio_english?: ?string, bio_somali?: ?string, bio_arabic?: ?string}  $model
     */
    private static function pick(object $model, string $field): ?string
    {
        $map = [
            'en' => "{$field}_english",
            'so' => "{$field}_somali",
            'ar' => "{$field}_arabic",
        ];

        $locale = self::locale();
        $primary = $model->{$map[$locale]} ?? null;

        if (filled($primary)) {
            return $primary;
        }

        foreach (['en', 'so', 'ar'] as $fallback) {
            $value = $model->{$map[$fallback]} ?? null;

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }
}
