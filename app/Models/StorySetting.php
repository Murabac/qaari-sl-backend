<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorySetting extends Model
{
    protected $fillable = [
        'hero_mission',
        'closing_note',
        'partners_section_enabled',
    ];

    protected function casts(): array
    {
        return [
            'partners_section_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'hero_mission' => "Xulka Quraa'da began as an idea to give Somali reciters a wider home — a place where their voices can be heard with dignity, preserved with care, and shared with the community at home and across the diaspora.",
            'closing_note' => 'With gratitude to our board, patrons, and every reciter who entrusts their voice to this archive — this project exists in service of them.',
            'partners_section_enabled' => true,
        ]);
    }
}
