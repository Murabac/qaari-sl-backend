<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ayah extends Model
{
    protected $fillable = [
        'surah_id',
        'number',
        'text_uthmani',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
        ];
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }
}
