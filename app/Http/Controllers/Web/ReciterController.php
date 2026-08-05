<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Reciter;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReciterController extends Controller
{
    public function index(Request $request): View
    {
        $reciters = Reciter::query()
            ->withApprovedRecitations()
            ->withCount('approvedRecitations')
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
            ->search($request->query('q'))
            ->orderBy('name_english')
            ->paginate(12)
            ->withQueryString();

        $regions = Reciter::query()
            ->withApprovedRecitations()
            ->whereNotNull('region')
            ->where('region', '!=', '')
            ->distinct()
            ->orderBy('region')
            ->pluck('region');

        return view('reciters.index', [
            'reciters' => $reciters,
            'regions' => $regions,
            'q' => $request->query('q'),
            'region' => $request->query('region'),
        ]);
    }

    public function show(Reciter $reciter): View
    {
        abort_unless($reciter->approvedRecitations()->exists(), 404);

        $reciter->load([
            'approvedRecitations.surah',
        ]);

        $reciter->setRelation(
            'approvedRecitations',
            $reciter->approvedRecitations
                ->sortBy(fn ($recitation) => $recitation->surah?->number ?? 0)
                ->values(),
        );

        $tracks = $reciter->approvedRecitations->map(fn ($recitation) => [
            'recitation' => $recitation,
            'audio_url' => MediaUrl::temporary('r2', $recitation->audio_url),
        ]);

        return view('reciters.show', [
            'reciter' => $reciter,
            'tracks' => $tracks,
            'photoUrl' => MediaUrl::temporary('r2', $reciter->photo_url),
        ]);
    }
}
