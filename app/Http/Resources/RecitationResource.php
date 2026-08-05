<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Recitation */
class RecitationResource extends JsonResource
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
            'reciter' => new ReciterResource($this->whenLoaded('reciter')),
            'surah' => new SurahResource($this->whenLoaded('surah')),
        ];
    }
}
