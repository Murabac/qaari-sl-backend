<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MomentComment extends Model
{
    protected $fillable = [
        'user_id',
        'moment_id',
        'body',
    ];

    protected static function booted(): void
    {
        static::created(function (MomentComment $comment): void {
            Moment::query()->whereKey($comment->moment_id)->increment('comments_count');
        });

        static::deleted(function (MomentComment $comment): void {
            Moment::query()
                ->whereKey($comment->moment_id)
                ->where('comments_count', '>', 0)
                ->decrement('comments_count');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moment(): BelongsTo
    {
        return $this->belongsTo(Moment::class);
    }
}
