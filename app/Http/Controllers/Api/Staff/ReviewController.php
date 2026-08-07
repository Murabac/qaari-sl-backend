<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\RecitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Staff\StaffRecitationResource;
use App\Models\Recitation;
use App\Services\RecitationReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function __construct(
        private readonly RecitationReviewService $reviews,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->isReviewer(), 403);

        $status = $request->string('status')->toString() ?: RecitationStatus::PendingReview->value;

        $query = Recitation::query()
            ->with(['reciter', 'surah', 'creator', 'reviewNotes.user'])
            ->where('status', $status)
            ->latest('submitted_at')
            ->latest('id');

        if ($reciterId = $request->integer('reciter_id')) {
            $query->where('reciter_id', $reciterId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->whereHas('reciter', fn ($rq) => $rq->search($search))
                    ->orWhereHas('surah', function ($sq) use ($search): void {
                        $like = '%'.$search.'%';
                        $sq->where('name_english', 'like', $like)
                            ->orWhere('name_somali', 'like', $like)
                            ->orWhere('name_arabic', 'like', $like);
                    });
            });
        }

        return StaffRecitationResource::collection($query->paginate(50));
    }

    public function approve(Request $request, Recitation $recitation): StaffRecitationResource
    {
        abort_unless($request->user()->isReviewer(), 403);
        $this->authorize('review', $recitation);

        $recitation = $this->reviews->approve($recitation, $request->user());

        return new StaffRecitationResource($recitation);
    }

    public function reject(Request $request, Recitation $recitation): StaffRecitationResource
    {
        abort_unless($request->user()->isReviewer(), 403);
        $this->authorize('review', $recitation);

        $validated = $request->validate([
            'voice_note' => ['nullable', 'file', 'max:20480', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/webm,audio/mp4,audio/m4a,audio/x-m4a,audio/ogg,application/octet-stream'],
            'caption' => ['nullable', 'string', 'max:255'],
            'recording' => ['nullable', 'array'],
            'recording.data' => ['nullable', 'string'],
            'recording.duration' => ['nullable', 'numeric'],
        ]);

        $recitation = $this->reviews->reject(
            $recitation,
            $request->user(),
            $request->file('voice_note'),
            [
                'caption' => $validated['caption'] ?? null,
                'recording' => $validated['recording'] ?? null,
            ],
        );

        return new StaffRecitationResource($recitation);
    }
}
