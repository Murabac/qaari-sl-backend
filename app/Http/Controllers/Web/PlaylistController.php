<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Recitation;
use App\Support\MediaUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlaylistController extends Controller
{
    public function index(Request $request): View
    {
        $playlists = $request->user()
            ->playlists()
            ->withCount('items')
            ->latest()
            ->get();

        return view('library.playlists', [
            'playlists' => $playlists,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $playlist = $request->user()->playlists()->create($validated);

        return redirect()
            ->route('library.playlists.show', $playlist)
            ->with('status', __('site.playlist_created'));
    }

    public function show(Request $request, Playlist $playlist): View
    {
        $this->authorizeOwner($request, $playlist);

        $playlist->load([
            'items.recitation' => fn ($q) => $q->approved()->with(['reciter', 'surah']),
        ]);

        $tracks = $playlist->items
            ->filter(fn (PlaylistItem $item) => $item->recitation !== null)
            ->map(fn (PlaylistItem $item) => [
                'item' => $item,
                'recitation' => $item->recitation,
                'audio_url' => MediaUrl::temporary('r2', $item->recitation->audio_url),
            ]);

        return view('library.playlist-show', [
            'playlist' => $playlist,
            'tracks' => $tracks,
        ]);
    }

    public function update(Request $request, Playlist $playlist): RedirectResponse
    {
        $this->authorizeOwner($request, $playlist);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $playlist->update($validated);

        return back()->with('status', __('site.playlist_updated'));
    }

    public function destroy(Request $request, Playlist $playlist): RedirectResponse
    {
        $this->authorizeOwner($request, $playlist);
        $playlist->delete();

        return redirect()
            ->route('library.playlists')
            ->with('status', __('site.playlist_deleted'));
    }

    public function addItem(Request $request, Playlist $playlist): RedirectResponse
    {
        $this->authorizeOwner($request, $playlist);

        $validated = $request->validate([
            'recitation_id' => ['required', 'integer', 'exists:recitations,id'],
        ]);

        $recitation = Recitation::query()
            ->approved()
            ->findOrFail($validated['recitation_id']);

        PlaylistItem::query()->firstOrCreate(
            [
                'playlist_id' => $playlist->id,
                'recitation_id' => $recitation->id,
            ],
            [
                'sort_order' => ((int) $playlist->items()->max('sort_order')) + 1,
            ],
        );

        return back()->with('status', __('site.added_to_playlist'));
    }

    public function removeItem(Request $request, Playlist $playlist, PlaylistItem $item): RedirectResponse
    {
        $this->authorizeOwner($request, $playlist);

        abort_unless((int) $item->playlist_id === (int) $playlist->id, 404);

        $item->delete();

        return back()->with('status', __('site.removed_from_playlist'));
    }

    private function authorizeOwner(Request $request, Playlist $playlist): void
    {
        abort_unless((int) $playlist->user_id === (int) $request->user()->id, 404);
    }
}
