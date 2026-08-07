<?php

namespace App\Models;

use App\Enums\RecitationStatus;
use App\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recitation extends Model
{
    protected $fillable = [
        'reciter_id',
        'surah_id',
        'audio_url',
        'duration',
        'file_size',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'created_by',
        'sync_status',
        'synced_at',
        'sync_error',
        'sync_method',
        'manual_sync_ayah',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'file_size' => 'integer',
            'status' => RecitationStatus::class,
            'sync_status' => SyncStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'synced_at' => 'datetime',
            'manual_sync_ayah' => 'integer',
        ];
    }

    public function ayahTimings(): HasMany
    {
        return $this->hasMany(RecitationAyahTiming::class)->orderBy('ayah_number');
    }

    public function isTextSynced(): bool
    {
        return $this->sync_status === SyncStatus::Synced
            && $this->ayahTimings()->exists();
    }

    /**
     * @return list<float>
     */
    public function ayahStartSeconds(): array
    {
        return $this->ayahTimings
            ->map(fn (RecitationAyahTiming $timing): float => $timing->start_ms / 1000)
            ->values()
            ->all();
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

    public function reviewNotes(): HasMany
    {
        return $this->hasMany(RecitationReviewNote::class)->latest();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function playlistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', RecitationStatus::Approved);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->created_by === (int) $user->id;
    }

    public function canBeEditedByProduction(): bool
    {
        return in_array($this->status, [RecitationStatus::Draft, RecitationStatus::Rejected], true);
    }
}
