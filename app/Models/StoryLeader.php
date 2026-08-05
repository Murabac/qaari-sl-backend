<?php

namespace App\Models;

use App\Enums\StoryLeaderTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoryLeader extends Model
{
    protected $fillable = [
        'name',
        'title',
        'photo_url',
        'tier',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tier' => StoryLeaderTier::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeTier(Builder $query, StoryLeaderTier $tier): Builder
    {
        return $query->where('tier', $tier);
    }
}
