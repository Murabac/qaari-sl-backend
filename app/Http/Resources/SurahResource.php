<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Surah */
class SurahResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'name_arabic' => $this->name_arabic,
            'name_somali' => $this->name_somali,
            'name_english' => $this->name_english,
            'verse_count' => $this->verse_count,
        ];
    }
}
