<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReciterResource;
use App\Models\Reciter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReciterController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $reciters = Reciter::query()
            ->withApprovedRecitations()
            ->withCount('approvedRecitations')
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
            ->search($request->query('q'))
            ->orderBy('name_english')
            ->paginate(min((int) $request->integer('per_page', 15), 50));

        return ReciterResource::collection($reciters);
    }

    public function show(Reciter $reciter): ReciterResource
    {
        abort_unless(
            $reciter->approvedRecitations()->exists(),
            404,
        );

        $reciter->load([
            'approvedRecitations.surah',
        ]);

        $reciter->setRelation(
            'approvedRecitations',
            $reciter->approvedRecitations
                ->sortBy(fn ($recitation) => $recitation->surah?->number ?? 0)
                ->values(),
        );

        $reciter->loadCount('approvedRecitations');

        return new ReciterResource($reciter);
    }
}
