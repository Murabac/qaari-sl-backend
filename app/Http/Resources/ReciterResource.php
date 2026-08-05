<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Reciter */
class ReciterResource extends JsonResource
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
            'approved_recitations_count' => $this->when(
                isset($this->approved_recitations_count),
                fn () => (int) $this->approved_recitations_count,
            ),
            'recitations' => RecitationResource::collection($this->whenLoaded('approvedRecitations')),
        ];
    }
}
