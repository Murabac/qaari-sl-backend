<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaylistResource;
use App\Http\Resources\RecitationResource;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Recitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PlaylistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $playlists = $request->user()
            ->playlists()
            ->withCount('items')
            ->latest()
            ->paginate(min((int) $request->integer('per_page', 15), 50));

        return PlaylistResource::collection($playlists);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $playlist = $request->user()->playlists()->create([
            'name' => $validated['name'],
        ]);

        $playlist->loadCount('items');

        return response()->json([
            'data' => (new PlaylistResource($playlist))->resolve(),
        ], 201);
    }

    public function show(Request $request, Playlist $playlist): PlaylistResource
    {
        $this->authorizeOwner($request, $playlist);

        $playlist->load([
            'items.recitation' => fn ($q) => $q->approved()->with(['reciter', 'surah']),
        ]);
        $playlist->loadCount('items');

        return new PlaylistResource($playlist);
    }

    public function update(Request $request, Playlist $playlist): PlaylistResource
    {
        $this->authorizeOwner($request, $playlist);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $playlist->update($validated);
        $playlist->loadCount('items');

        return new PlaylistResource($playlist);
    }

    public function destroy(Request $request, Playlist $playlist): JsonResponse
    {
        $this->authorizeOwner($request, $playlist);

        $playlist->delete();

        return response()->json([
            'data' => [
                'message' => 'Playlist deleted',
            ],
        ]);
    }

    public function addItem(Request $request, Playlist $playlist): JsonResponse
    {
        $this->authorizeOwner($request, $playlist);

        $validated = $request->validate([
            'recitation_id' => ['required', 'integer', 'exists:recitations,id'],
        ]);

        $recitation = Recitation::query()
            ->approved()
            ->findOrFail($validated['recitation_id']);

        $item = PlaylistItem::query()->firstOrCreate(
            [
                'playlist_id' => $playlist->id,
                'recitation_id' => $recitation->id,
            ],
            [
                'sort_order' => ((int) $playlist->items()->max('sort_order')) + 1,
            ],
        );

        $item->load(['recitation.reciter', 'recitation.surah']);

        return response()->json([
            'data' => [
                'id' => $item->id,
                'sort_order' => $item->sort_order,
                'recitation' => (new RecitationResource($item->recitation))->resolve(),
            ],
        ], 201);
    }

    public function removeItem(Request $request, Playlist $playlist, int $item): JsonResponse
    {
        $this->authorizeOwner($request, $playlist);

        PlaylistItem::query()
            ->where('playlist_id', $playlist->id)
            ->where('id', $item)
            ->delete();

        return response()->json([
            'data' => [
                'message' => 'Item removed',
            ],
        ]);
    }

    public function reorderItems(Request $request, Playlist $playlist): PlaylistResource
    {
        $this->authorizeOwner($request, $playlist);

        $validated = $request->validate([
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'distinct'],
        ]);

        $ownedIds = $playlist->items()->pluck('id')->all();
        $incoming = $validated['item_ids'];

        abort_unless(
            count($incoming) === count($ownedIds)
            && empty(array_diff($incoming, $ownedIds)),
            422,
            'item_ids must include every playlist item exactly once.',
        );

        DB::transaction(function () use ($incoming): void {
            foreach ($incoming as $index => $id) {
                PlaylistItem::query()->whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });

        $playlist->load([
            'items.recitation' => fn ($q) => $q->approved()->with(['reciter', 'surah']),
        ]);
        $playlist->loadCount('items');

        return new PlaylistResource($playlist);
    }

    private function authorizeOwner(Request $request, Playlist $playlist): void
    {
        abort_unless((int) $playlist->user_id === (int) $request->user()->id, 404);
    }
}
