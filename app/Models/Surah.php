<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function ayahs(): HasMany
    {
        return $this->hasMany(Ayah::class)->orderBy('number');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like, $term): void {
            $q->where('name_english', 'like', $like)
                ->orWhere('name_somali', 'like', $like)
                ->orWhere('name_arabic', 'like', $like);

            if (ctype_digit($term)) {
                $q->orWhere('number', (int) $term);
            }
        });
    }
}
