<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MomentResource;
use App\Models\Moment;
use App\Models\MomentLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MomentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $moments = Moment::query()
            ->approved()
            ->with(['reciter', 'surah'])
            ->when(
                $request->user(),
                fn ($query) => $query->withExists([
                    'likes as liked' => fn ($likes) => $likes->where('user_id', $request->user()->id),
                ]),
            )
            ->latest('id')
            ->paginate(min((int) $request->integer('per_page', 20), 50));

        return MomentResource::collection($moments);
    }

    public function show(Moment $moment): MomentResource
    {
        abort_unless($moment->status?->value === 'approved', 404);

        $moment->load(['reciter', 'surah']);

        if ($request = request()->user()) {
            $moment->loadExists([
                'likes as liked' => fn ($likes) => $likes->where('user_id', $request->id),
            ]);
        }

        return new MomentResource($moment);
    }

    public function like(Request $request, Moment $moment): JsonResponse
    {
        abort_unless($moment->status?->value === 'approved', 404);

        $created = false;

        DB::transaction(function () use ($request, $moment, &$created): void {
            $like = MomentLike::query()->firstOrCreate([
                'user_id' => $request->user()->id,
                'moment_id' => $moment->id,
            ]);

            if ($like->wasRecentlyCreated) {
                $moment->increment('likes_count');
                $created = true;
            }
        });

        $moment->refresh()->load(['reciter', 'surah']);
        if ($request->user()) {
            $moment->loadExists([
                'likes as liked' => fn ($likes) => $likes->where('user_id', $request->user()->id),
            ]);
        }

        return response()->json([
            'data' => (new MomentResource($moment))->resolve(),
            'meta' => ['created' => $created],
        ], $created ? 201 : 200);
    }

    public function unlike(Request $request, Moment $moment): JsonResponse
    {
        abort_unless($moment->status?->value === 'approved', 404);

        DB::transaction(function () use ($request, $moment): void {
            $deleted = MomentLike::query()
                ->where('user_id', $request->user()->id)
                ->where('moment_id', $moment->id)
                ->delete();

            if ($deleted) {
                Moment::query()
                    ->whereKey($moment->id)
                    ->where('likes_count', '>', 0)
                    ->decrement('likes_count');
            }
        });

        $moment->refresh()->load(['reciter', 'surah']);
        if ($request->user()) {
            $moment->loadExists([
                'likes as liked' => fn ($likes) => $likes->where('user_id', $request->user()->id),
            ]);
        }

        return response()->json([
            'data' => (new MomentResource($moment))->resolve(),
        ]);
    }
}
