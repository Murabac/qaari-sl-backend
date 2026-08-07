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

        $reciterName = LocaleText::reciterName($recitation->reciter);

        $siblingRecitations = Recitation::query()
            ->approved()
            ->where('reciter_id', $recitation->reciter_id)
            ->with(['surah', 'ayahTimings'])
            ->get()
            ->sortBy(fn (Recitation $item) => $item->surah?->number ?? $item->surah_id)
            ->values();

        $playerQueue = $siblingRecitations->map(function (Recitation $item) use ($reciterName) {
            $itemTitle = $item->surah
                ? (($item->surah->number ?? '').'. '.LocaleText::surahName($item->surah))
                : __('site.surah');

            $itemAudio = MediaUrl::temporary('r2', $item->audio_url);

            if (! $itemAudio) {
                return null;
            }

            return [
                'id' => $item->id,
                'title' => $itemTitle,
                'subtitle' => $reciterName,
                'src' => $itemAudio,
                'durationSeconds' => (int) ($item->duration ?? 0),
                'reciterUrl' => route('reciters.show', $item->reciter_id),
                'followUrl' => route('follow-along.show', $item),
                'shareUrl' => route('reciters.show', ['reciter' => $item->reciter_id, 'play' => $item->id]),
                'verseCount' => (int) ($item->surah->verse_count ?? 0),
                'ayahStarts' => $item->ayahTimings
                    ->map(fn ($timing) => round($timing->start_ms / 1000, 3))
                    ->values()
                    ->all(),
            ];
        })->filter()->values()->all();

        return view('follow-along', [
            'recitation' => $recitation,
            'ayahs' => $ayahs,
            'ayahStarts' => $ayahStarts,
            'audioUrl' => $audioUrl,
            'title' => $title,
            'shareUrl' => $shareUrl,
            'playerQueue' => $playerQueue,
            'isFavorite' => $request->user()
                ? $request->user()->favorites()->where('recitation_id', $recitation->id)->exists()
                : false,
            'playlists' => $request->user()
                ? $request->user()->playlists()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }
}
