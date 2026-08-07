<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecitationResource;
use App\Models\Ayah;
use App\Models\Recitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecitationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $recitations = Recitation::query()
            ->approved()
            ->with(['reciter', 'surah'])
            ->when($request->filled('reciter_id'), fn ($q) => $q->where('reciter_id', $request->integer('reciter_id')))
            ->when($request->filled('surah_id'), fn ($q) => $q->where('surah_id', $request->integer('surah_id')))
            ->orderBy('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return RecitationResource::collection($recitations);
    }

    public function show(int $recitation): RecitationResource
    {
        $record = Recitation::query()
            ->approved()
            ->with(['reciter', 'surah'])
            ->findOrFail($recitation);

        return new RecitationResource($record);
    }

    public function followAlong(int $recitation): JsonResponse
    {
        $record = Recitation::query()
            ->approved()
            ->with(['reciter', 'surah', 'ayahTimings'])
            ->findOrFail($recitation);

        $ayahs = Ayah::query()
            ->where('surah_id', $record->surah_id)
            ->orderBy('number')
            ->get(['number', 'text_uthmani'])
            ->map(fn (Ayah $ayah): array => [
                'number' => (int) $ayah->number,
                'text_uthmani' => (string) $ayah->text_uthmani,
            ])
            ->values()
            ->all();

        $ayahStarts = $record->ayahTimings
            ->sortBy('ayah_number')
            ->map(fn ($timing) => round($timing->start_ms / 1000, 3))
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'recitation' => (new RecitationResource($record))->resolve(),
                'ayahs' => $ayahs,
                'ayah_starts' => $ayahStarts,
            ],
        ]);
    }
}
