<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Surah extends Model
{
    protected $fillable = [
        'number',
        'name_arabic',
        'name_somali',
        'name_english',
        'verse_count',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'verse_count' => 'integer',
        ];
    }

    public function recitations(): HasMany
    {
        return $this->hasMany(Recitation::class);
    }
}
