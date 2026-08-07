<?php

namespace App\Enums;

enum SyncStatus: string
{
    case Pending = 'pending';
    case Syncing = 'syncing';
    case Synced = 'synced';
    case Failed = 'failed';
    case MissingAudio = 'missing_audio';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending sync',
            self::Syncing => 'Syncing…',
            self::Synced => 'Text synced',
            self::Failed => 'Sync failed',
            self::MissingAudio => 'No audio',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Syncing => 'warning',
            self::Synced => 'success',
            self::Failed => 'danger',
            self::MissingAudio => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
