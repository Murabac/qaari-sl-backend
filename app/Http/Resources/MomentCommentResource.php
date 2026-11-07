<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\MomentComment */
class MomentCommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'user_name' => $this->user?->name,
            'mine' => $request->user() !== null && (int) $this->user_id === (int) $request->user()->id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
