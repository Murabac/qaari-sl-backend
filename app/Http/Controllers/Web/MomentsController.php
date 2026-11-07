<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\V1\MomentController as ApiMomentController;
use App\Http\Controllers\Controller;
use App\Models\Moment;
use App\Models\MomentComment;
use App\Models\ReciterFollow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MomentsController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $filter = (string) $request->string('filter', 'latest');
        $user = $request->user();

        if (in_array($filter, ['following', 'saved'], true) && $user === null) {
            return redirect()->guest(route('login'));
        }

        $moments = Moment::query()
            ->approved()
            ->with([
                'reciter',
                'surah',
                'comments' => fn ($query) => $query->with('user:id,name')->latest('id'),
            ])
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
            ->when($filter === 'trending', fn ($query) => $query->orderByDesc('likes_count')->orderByDesc('id'))
            ->when($filter === 'following' && $user, function ($query) use ($user): void {
                $ids = ReciterFollow::query()->where('user_id', $user->id)->pluck('reciter_id');
                $query->whereIn('reciter_id', $ids)->latest('id');
            })
            ->when($filter === 'saved' && $user, function ($query) use ($user): void {
                $query->whereHas('saves', fn ($saves) => $saves->where('user_id', $user->id))->latest('id');
            })
            ->when(! in_array($filter, ['trending', 'following', 'saved'], true), fn ($query) => $query->latest('id'))
            ->paginate(12)
            ->withQueryString();

        return view('moments.index', [
            'moments' => $moments,
            'filter' => in_array($filter, ['latest', 'trending', 'following', 'saved'], true) ? $filter : 'latest',
            'search' => (string) $request->string('q'),
        ]);
    }

    public function like(Request $request, Moment $moment): RedirectResponse
    {
        app(ApiMomentController::class)->like($request, $moment);

        return back();
    }

    public function unlike(Request $request, Moment $moment): RedirectResponse
    {
        app(ApiMomentController::class)->unlike($request, $moment);

        return back();
    }

    public function save(Request $request, Moment $moment): RedirectResponse
    {
        app(ApiMomentController::class)->save($request, $moment);

        return back();
    }

    public function unsave(Request $request, Moment $moment): RedirectResponse
    {
        app(ApiMomentController::class)->unsave($request, $moment);

        return back();
    }

    public function comment(Request $request, Moment $moment): RedirectResponse
    {
        app(ApiMomentController::class)->storeComment($request, $moment);

        return back();
    }

    public function destroyComment(Request $request, Moment $moment, MomentComment $comment): RedirectResponse
    {
        app(ApiMomentController::class)->destroyComment($request, $moment, $comment);

        return back();
    }
}
