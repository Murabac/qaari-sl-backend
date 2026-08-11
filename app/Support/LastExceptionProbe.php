<?php

namespace App\Support;

use Throwable;

class LastExceptionProbe
{
    public static function token(): string
    {
        return hash_hmac('sha256', 'qaari-last-error', (string) config('app.key'));
    }

    public static function path(): string
    {
        return storage_path('logs/last-exception.json');
    }

    public static function capture(Throwable $e): void
    {
        try {
            $payload = [
                'at' => now()->toIso8601String(),
                'class' => $e::class,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile().':'.$e->getLine(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'user_id' => auth()->id(),
                'trace' => collect($e->getTrace())
                    ->take(25)
                    ->map(fn (array $frame): string => sprintf(
                        '%s%s%s(%s)',
                        $frame['file'] ?? '[internal]',
                        isset($frame['line']) ? ':'.$frame['line'] : '',
                        isset($frame['function']) ? ' '.$frame['function'] : '',
                        isset($frame['class']) ? $frame['class'] : '',
                    ))
                    ->all(),
            ];

            @file_put_contents(self::path(), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (Throwable) {
            // Never break the real exception flow.
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(): ?array
    {
        $path = self::path();

        if (! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }
}
