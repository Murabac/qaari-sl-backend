<?php

namespace App\Http\Resources\Staff;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\RecitationReviewNote */
class StaffReviewNoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recitation_id' => $this->recitation_id,
            'audio_url' => MediaUrl::temporary('r2', $this->audio_url),
            'duration' => $this->duration,
            'file_size' => $this->file_size,
            'caption' => $this->caption,
            'status_at_time' => $this->status_at_time?->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
        ];
    }
}
