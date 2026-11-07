<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MomentCommentResource;
use App\Http\Resources\MomentResource;
use App\Models\Moment;
use App\Models\MomentComment;
use App\Models\MomentLike;
use App\Models\MomentSave;
use App\Models\ReciterFollow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MomentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $filter = (string) $request->string('filter', 'latest');
        $user = $request->user();

        if (in_array($filter, ['following', 'saved'], true) && $user === null) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $moments = Moment::query()
            ->approved()
            ->with(['reciter', 'surah'])
            ->when($user, function ($query) use ($user): void {
                $query->withExists([
                    'likes as liked' => fn ($likes) => $likes->where('user_id', $user->id),
                    'saves as saved' => fn ($saves) => $saves->where('user_id', $user->id),
                ]);
            })
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('title', 'like', $term)
                        ->orWhere('caption', 'like', $term)
                        ->orWhereHas('reciter', function ($reciter) use ($term): void {
                            $reciter->where('name_english', 'like', $term)
                                ->orWhere('name_somali', 'like', $term)
                                ->orWhere('name_arabic', 'like', $term);
                        });
                });
            })
            ->when($request->filled('tag'), function ($query) use ($request): void {
                $tag = preg_replace('/[^\p{L}\p{N}_]+/u', '', (string) $request->string('tag')) ?? '';
                if ($tag !== '') {
                    $query->where('caption', 'like', '%#'.$tag.'%');
                }
            })
            ->when($filter === 'trending', fn ($query) => $query->orderByDesc('likes_count')->orderByDesc('id'))
            ->when($filter === 'following' && $user, function ($query) use ($user): void {
                $ids = ReciterFollow::query()->where('user_id', $user->id)->pluck('reciter_id');
                $query->whereIn('reciter_id', $ids)->latest('id');
            })
            ->when($filter === 'saved' && $user, function ($query) use ($user): void {
                $query->whereHas('saves', fn ($saves) => $saves->where('user_id', $user->id))->latest('id');
            })
            ->when(! in_array($filter, ['trending', 'following', 'saved'], true), fn ($query) => $query->latest('id'))
            ->paginate(min((int) $request->integer('per_page', 20), 50));

        if ($user) {
            $followed = ReciterFollow::query()->where('user_id', $user->id)->pluck('reciter_id');
            $moments->getCollection()->each(function (Moment $moment) use ($followed): void {
                $moment->setAttribute(
                    'followed_reciter',
                    $moment->reciter_id !== null && $followed->contains($moment->reciter_id),
                );
            });
        }

        return MomentResource::collection($moments);
    }

    public function show(Moment $moment): MomentResource
    {
        abort_unless($moment->status?->value === 'approved', 404);

        $moment->load(['reciter', 'surah']);

        if ($request = request()->user()) {
            $moment->loadExists([
                'likes as liked' => fn ($likes) => $likes->where('user_id', $request->id),
                'saves as saved' => fn ($saves) => $saves->where('user_id', $request->id),
            ]);
            $moment->setAttribute(
                'followed_reciter',
                $moment->reciter_id !== null && ReciterFollow::query()
                    ->where('user_id', $request->id)
                    ->where('reciter_id', $moment->reciter_id)
                    ->exists(),
            );
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

    public function save(Request $request, Moment $moment): JsonResponse
    {
        abort_unless($moment->status?->value === 'approved', 404);

        $created = false;

        DB::transaction(function () use ($request, $moment, &$created): void {
            $save = MomentSave::query()->firstOrCreate([
                'user_id' => $request->user()->id,
                'moment_id' => $moment->id,
            ]);

            if ($save->wasRecentlyCreated) {
                $moment->increment('saves_count');
                $created = true;
            }
        });

        return response()->json([
            'data' => (new MomentResource($this->present($request, $moment)))->resolve(),
        ], $created ? 201 : 200);
    }

    public function unsave(Request $request, Moment $moment): JsonResponse
    {
        abort_unless($moment->status?->value === 'approved', 404);

        DB::transaction(function () use ($request, $moment): void {
            $deleted = MomentSave::query()
                ->where('user_id', $request->user()->id)
                ->where('moment_id', $moment->id)
                ->delete();

            if ($deleted) {
                Moment::query()
                    ->whereKey($moment->id)
                    ->where('saves_count', '>', 0)
                    ->decrement('saves_count');
            }
        });

        return response()->json([
            'data' => (new MomentResource($this->present($request, $moment)))->resolve(),
        ]);
    }

    public function comments(Moment $moment): AnonymousResourceCollection
    {
        abort_unless($moment->status?->value === 'approved', 404);

        $comments = $moment->comments()->with('user:id,name')->latest('id')->paginate(30);

        return MomentCommentResource::collection($comments);
    }

    public function storeComment(Request $request, Moment $moment): JsonResponse
    {
        abort_unless($moment->status?->value === 'approved', 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $comment = $moment->comments()->create([
            'user_id' => $request->user()->id,
            'body' => trim($data['body']),
        ]);
        $comment->load('user:id,name');

        return response()->json([
            'data' => (new MomentCommentResource($comment))->resolve(),
        ], 201);
    }

    public function destroyComment(Request $request, Moment $moment, MomentComment $comment): JsonResponse
    {
        abort_unless($comment->moment_id === $moment->id, 404);
        abort_unless((int) $comment->user_id === (int) $request->user()->id, 403);

        $comment->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    private function present(Request $request, Moment $moment): Moment
    {
        $moment->refresh()->load(['reciter', 'surah']);
        if ($request->user()) {
            $moment->loadExists([
                'likes as liked' => fn ($likes) => $likes->where('user_id', $request->user()->id),
                'saves as saved' => fn ($saves) => $saves->where('user_id', $request->user()->id),
            ]);
            $moment->setAttribute(
                'followed_reciter',
                $moment->reciter_id !== null && ReciterFollow::query()
                    ->where('user_id', $request->user()->id)
                    ->where('reciter_id', $moment->reciter_id)
                    ->exists(),
            );
        }

        return $moment;
    }
}
