<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecitationResource;
use App\Models\Favorite;
use App\Models\Recitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FavoriteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $recitations = $request->user()
            ->favoriteRecitations()
            ->approved()
            ->with(['reciter', 'surah'])
            ->latest('favorites.created_at')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return RecitationResource::collection($recitations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recitation_id' => ['required', 'integer', 'exists:recitations,id'],
        ]);

        $recitation = Recitation::query()
            ->approved()
            ->findOrFail($validated['recitation_id']);

        Favorite::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'recitation_id' => $recitation->id,
        ]);

        return response()->json([
            'data' => (new RecitationResource($recitation->load(['reciter', 'surah'])))->resolve(),
        ], 201);
    }

    public function destroy(Request $request, int $recitation): JsonResponse
    {
        Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('recitation_id', $recitation)
            ->delete();

        return response()->json([
            'data' => [
                'message' => 'Removed from favorites',
            ],
        ]);
    }
}
