<?php

namespace App\Models;

use App\Enums\RecitationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecitationReviewNote extends Model
{
    protected $fillable = [
        'recitation_id',
        'user_id',
        'audio_url',
        'duration',
        'file_size',
        'caption',
        'status_at_time',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'file_size' => 'integer',
            'status_at_time' => RecitationStatus::class,
        ];
    }

    public function recitation(): BelongsTo
    {
        return $this->belongsTo(Recitation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
