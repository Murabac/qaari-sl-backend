<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Moment */
class MomentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $minutes = (int) config('moments.signed_url_minutes', 120);

        $linkedRecitationId = null;
        if ($this->reciter_id && $this->surah_id) {
            $linkedRecitationId = \App\Models\Recitation::query()
                ->approved()
                ->where('reciter_id', $this->reciter_id)
                ->where('surah_id', $this->surah_id)
                ->value('id');
        }

        return [
            'id' => $this->id,
            'reciter_id' => $this->reciter_id,
            'surah_id' => $this->surah_id,
            'ayah_number' => $this->ayah_number,
            'title' => $this->title,
            'caption' => $this->caption,
            'video_url' => MediaUrl::temporary('r2', $this->video_url, $minutes),
            'poster_url' => MediaUrl::temporary('r2', $this->poster_url, $minutes),
            'duration' => $this->duration,
            'width' => $this->width,
            'height' => $this->height,
            'likes_count' => (int) $this->likes_count,
            'liked' => (bool) ($this->liked ?? false),
            'status' => $this->status?->value,
            'linked_recitation_id' => $linkedRecitationId,
            'reciter' => new ReciterResource($this->whenLoaded('reciter')),
            'surah' => new SurahResource($this->whenLoaded('surah')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
