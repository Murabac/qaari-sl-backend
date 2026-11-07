<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReciterResource;
use App\Models\Reciter;
use App\Models\ReciterFollow;
use Illuminate\Http\JsonResponse;
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
        $this->markFollowed($reciter, request()->user());

        return new ReciterResource($reciter);
    }

    public function follow(Request $request, Reciter $reciter): JsonResponse
    {
        ReciterFollow::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'reciter_id' => $reciter->id,
        ]);

        $this->markFollowed($reciter, $request->user());

        return response()->json([
            'data' => (new ReciterResource($reciter))->resolve(),
        ]);
    }

    public function unfollow(Request $request, Reciter $reciter): JsonResponse
    {
        ReciterFollow::query()
            ->where('user_id', $request->user()->id)
            ->where('reciter_id', $reciter->id)
            ->delete();

        $this->markFollowed($reciter, $request->user());

        return response()->json([
            'data' => (new ReciterResource($reciter))->resolve(),
        ]);
    }

    private function markFollowed(Reciter $reciter, mixed $user): void
    {
        $reciter->setAttribute(
            'followed',
            $user !== null && ReciterFollow::query()
                ->where('user_id', $user->id)
                ->where('reciter_id', $reciter->id)
                ->exists(),
        );
    }
}
