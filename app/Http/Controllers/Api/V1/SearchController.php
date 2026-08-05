<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecitationResource;
use App\Http\Resources\ReciterResource;
use App\Http\Resources\SurahResource;
use App\Models\Recitation;
use App\Models\Reciter;
use App\Models\Surah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $q = $validated['q'];
        $limit = min((int) $request->integer('limit', 10), 25);

        $reciters = Reciter::query()
            ->withApprovedRecitations()
            ->withCount('approvedRecitations')
            ->search($q)
            ->orderBy('name_english')
            ->limit($limit)
            ->get();

        $surahs = Surah::query()
            ->search($q)
            ->orderBy('number')
            ->limit($limit)
            ->get();

        $recitations = Recitation::query()
            ->approved()
            ->with(['reciter', 'surah'])
            ->where(function ($query) use ($q): void {
                $like = '%'.$q.'%';
                $query->whereHas('reciter', function ($r) use ($like): void {
                    $r->where('name_english', 'like', $like)
                        ->orWhere('name_somali', 'like', $like)
                        ->orWhere('name_arabic', 'like', $like);
                })->orWhereHas('surah', function ($s) use ($like, $q): void {
                    $s->where('name_english', 'like', $like)
                        ->orWhere('name_somali', 'like', $like)
                        ->orWhere('name_arabic', 'like', $like);

                    if (ctype_digit($q)) {
                        $s->orWhere('number', (int) $q);
                    }
                });
            })
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => [
                'reciters' => ReciterResource::collection($reciters)->resolve(),
                'surahs' => SurahResource::collection($surahs)->resolve(),
                'recitations' => RecitationResource::collection($recitations)->resolve(),
            ],
        ]);
    }
}
