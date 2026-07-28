<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
    ];

    public function recitations(): HasMany
    {
        return $this->hasMany(Recitation::class);
    }
}
