<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReciterFollow extends Model
{
    protected $fillable = [
        'user_id',
        'reciter_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reciter(): BelongsTo
    {
        return $this->belongsTo(Reciter::class);
    }
}
