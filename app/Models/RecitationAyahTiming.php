<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecitationAyahTiming extends Model
{
    protected $fillable = [
        'recitation_id',
        'ayah_number',
        'start_ms',
        'end_ms',
    ];

    protected function casts(): array
    {
        return [
            'ayah_number' => 'integer',
            'start_ms' => 'integer',
            'end_ms' => 'integer',
        ];
    }

    public function recitation(): BelongsTo
    {
        return $this->belongsTo(Recitation::class);
    }

    public function startSeconds(): float
    {
        return $this->start_ms / 1000;
    }

    public function endSeconds(): float
    {
        return $this->end_ms / 1000;
    }
}
