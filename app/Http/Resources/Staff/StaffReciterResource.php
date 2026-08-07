<?php

namespace App\Http\Resources\Staff;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Reciter */
class StaffReciterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_somali' => $this->name_somali,
            'name_arabic' => $this->name_arabic,
            'name_english' => $this->name_english,
            'bio_somali' => $this->bio_somali,
            'bio_arabic' => $this->bio_arabic,
            'bio_english' => $this->bio_english,
            'photo_url' => MediaUrl::temporary('r2', $this->photo_url),
            'region' => $this->region,
            'created_by' => $this->created_by,
            'recitations_count' => $this->when(
                isset($this->recitations_count),
                fn () => (int) $this->recitations_count,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'recitations' => StaffRecitationResource::collection($this->whenLoaded('recitations')),
        ];
    }
}
