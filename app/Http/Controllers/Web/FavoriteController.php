<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Recitation;
use App\Support\MediaUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $favorites = $request->user()
            ->favoriteRecitations()
            ->approved()
            ->with(['reciter', 'surah'])
            ->latest('favorites.created_at')
            ->get()
            ->map(fn (Recitation $recitation) => [
                'recitation' => $recitation,
                'audio_url' => MediaUrl::temporary('r2', $recitation->audio_url),
            ]);

        return view('library.favorites', [
            'favorites' => $favorites,
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        return back()->with('status', __('site.added_to_favorites'));
    }

    public function destroy(Request $request, Recitation $recitation): RedirectResponse
    {
        Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('recitation_id', $recitation->id)
            ->delete();

        return back()->with('status', __('site.removed_from_favorites'));
    }
}
