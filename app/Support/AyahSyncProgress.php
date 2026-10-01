<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class AyahSyncProgress
{
    public static function key(int $recitationId): string
    {
        return 'ayah-sync-progress:'.$recitationId;
    }

    /**
     * @param  array{label?: string, percent?: int, status?: string}  $data
     */
    public static function put(int $recitationId, array $data): void
    {
        Cache::put(self::key($recitationId), [
            'label' => (string) ($data['label'] ?? 'Working…'),
            'percent' => max(0, min(100, (int) ($data['percent'] ?? 0))),
            'status' => (string) ($data['status'] ?? 'syncing'),
            'updated_at' => now()->toIso8601String(),
        ], now()->addMinutes(30));
    }

    /**
     * @return array{label: string, percent: int, status: string, updated_at?: string}|null
     */
    public static function get(int $recitationId): ?array
    {
        $data = Cache::get(self::key($recitationId));

        return is_array($data) ? $data : null;
    }

    public static function clear(int $recitationId): void
    {
        Cache::forget(self::key($recitationId));
    }

    public static function queued(int $recitationId): void
    {
        self::put($recitationId, [
            'label' => 'Queued — waiting for the background worker…',
            'percent' => 5,
            'status' => 'pending',
        ]);
    }
}
