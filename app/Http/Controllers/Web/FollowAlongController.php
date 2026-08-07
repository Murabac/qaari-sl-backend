<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ayah;
use App\Models\Recitation;
use App\Support\LocaleText;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowAlongController extends Controller
{
    public function show(Request $request, Recitation $recitation): View
    {
        abort_unless(
            Recitation::query()->approved()->whereKey($recitation->id)->exists(),
            404,
        );

        $recitation->load(['reciter', 'surah']);

        abort_unless($recitation->reciter && $recitation->surah, 404);

        $ayahs = Ayah::query()
            ->where('surah_id', $recitation->surah_id)
            ->orderBy('number')
            ->get(['number', 'text_uthmani']);

        $recitation->loadMissing('ayahTimings');

        $ayahStarts = $recitation->ayahTimings
            ->map(fn ($timing) => round($timing->start_ms / 1000, 3))
            ->values()
            ->all();

        $title = $recitation->surah
            ? (($recitation->surah->number ?? '').'. '.LocaleText::surahName($recitation->surah))
            : __('site.surah');

        $audioUrl = MediaUrl::temporary('r2', $recitation->audio_url);
        $shareUrl = route('follow-along.show', $recitation);

        return view('follow-along', [
            'recitation' => $recitation,
            'ayahs' => $ayahs,
            'ayahStarts' => $ayahStarts,
            'audioUrl' => $audioUrl,
            'title' => $title,
            'shareUrl' => $shareUrl,
            'isFavorite' => $request->user()
                ? $request->user()->favorites()->where('recitation_id', $recitation->id)->exists()
                : false,
            'playlists' => $request->user()
                ? $request->user()->playlists()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }
}
