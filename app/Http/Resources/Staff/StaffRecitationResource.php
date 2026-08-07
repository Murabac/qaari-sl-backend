<?php

namespace App\Http\Resources\Staff;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Recitation */
class StaffRecitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reciter_id' => $this->reciter_id,
            'surah_id' => $this->surah_id,
            'audio_url' => MediaUrl::temporary('r2', $this->audio_url),
            'duration' => $this->duration,
            'file_size' => $this->file_size,
            'status' => $this->status?->value,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'reviewed_by' => $this->reviewed_by,
            'created_by' => $this->created_by,
            'sync_status' => $this->sync_status?->value,
            'sync_method' => $this->sync_method,
            'manual_sync_ayah' => $this->manual_sync_ayah,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'reciter' => $this->whenLoaded('reciter', fn () => new StaffReciterResource($this->reciter)),
            'surah' => $this->whenLoaded('surah', fn () => [
                'id' => $this->surah->id,
                'number' => $this->surah->number,
                'name_english' => $this->surah->name_english,
                'name_somali' => $this->surah->name_somali,
                'name_arabic' => $this->surah->name_arabic,
                'verse_count' => $this->surah->verse_count,
            ]),
            'review_notes' => StaffReviewNoteResource::collection($this->whenLoaded('reviewNotes')),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null),
        ];
    }
}
