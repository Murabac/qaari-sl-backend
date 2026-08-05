<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function approvedRecitations(): HasMany
    {
        return $this->hasMany(Recitation::class)->approved();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeWithApprovedRecitations(Builder $query): Builder
    {
        return $query->whereHas('recitations', fn (Builder $q) => $q->approved());
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('name_english', 'like', $like)
                ->orWhere('name_somali', 'like', $like)
                ->orWhere('name_arabic', 'like', $like);
        });
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->created_by === (int) $user->id;
    }
}
