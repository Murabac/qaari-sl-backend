<?php

namespace App\Models;

use App\Enums\MomentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Moment extends Model
{
    protected $fillable = [
        'reciter_id',
        'surah_id',
        'ayah_number',
        'title',
        'caption',
        'video_url',
        'poster_url',
        'duration',
        'width',
        'height',
        'file_size',
        'likes_count',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ayah_number' => 'integer',
            'duration' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'file_size' => 'integer',
            'likes_count' => 'integer',
            'status' => MomentStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reciter(): BelongsTo
    {
        return $this->belongsTo(Reciter::class);
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(MomentLike::class);
    }

    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'moment_likes')->withTimestamps();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', MomentStatus::Approved);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->created_by === (int) $user->id;
    }

    public function canBeEditedByProduction(): bool
    {
        return in_array($this->status, [MomentStatus::Draft, MomentStatus::Rejected], true);
    }
}
