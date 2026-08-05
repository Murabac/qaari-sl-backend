<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reciter extends Model
{
    protected $fillable = [
        'name_somali',
        'name_arabic',
        'name_english',
        'bio_somali',
        'bio_arabic',
        'bio_english',
        'photo_url',
        'region',
        'created_by',
    ];

    public function recitations(): HasMany
    {
        return $this->hasMany(Recitation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->created_by === (int) $user->id;
    }
}
