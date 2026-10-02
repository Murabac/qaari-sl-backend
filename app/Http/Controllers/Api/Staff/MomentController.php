<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\MomentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\MomentResource;
use App\Models\Moment;
use App\Support\AudioMetadata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MomentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Moment::class);

        $query = Moment::query()->with(['reciter', 'surah'])->latest('id');

        $user = $request->user();
        if ($user->isProduction() && ! $user->isReviewer()) {
            $query->where('created_by', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('reciter_id')) {
            $query->where('reciter_id', $request->integer('reciter_id'));
        }

        return MomentResource::collection(
            $query->paginate(min((int) $request->integer('per_page', 25), 100))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Moment::class);

        $maxKb = (int) config('moments.max_upload_kb', 81920);
        $maxDuration = (int) config('moments.max_duration_seconds', 60);

        $validated = $request->validate([
            'reciter_id' => ['nullable', 'integer', 'exists:reciters,id'],
            'surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'ayah_number' => ['nullable', 'integer', 'min:1', 'max:286'],
            'title' => ['nullable', 'string', 'max:120'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:'.$maxDuration],
            'width' => ['nullable', 'integer', 'min:1'],
            'height' => ['nullable', 'integer', 'min:1'],
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime', 'max:'.$maxKb],
            'poster' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $videoFile = $request->file('video');
        $meta = AudioMetadata::fromUpload($videoFile->getPathname(), 'r2');
        $duration = $validated['duration'] ?? $meta['duration'];

        if ($duration === null || $duration < 1) {
            throw ValidationException::withMessages([
                'video' => 'Could not detect video duration. Re-encode as MP4 or send duration.',
            ]);
        }

        $duration = min((int) $duration, $maxDuration);

        $videoPath = $videoFile->store(
            config('moments.video_directory', 'moments/videos'),
            'r2'
        );

        $posterPath = null;
        if ($request->hasFile('poster')) {
            $posterPath = $request->file('poster')->store(
                config('moments.poster_directory', 'moments/posters'),
                'r2'
            );
        }

        $moment = Moment::query()->create([
            'reciter_id' => $validated['reciter_id'] ?? null,
            'surah_id' => $validated['surah_id'] ?? null,
            'ayah_number' => $validated['ayah_number'] ?? null,
            'title' => $validated['title'] ?? null,
            'caption' => $validated['caption'] ?? null,
            'video_url' => $videoPath,
            'poster_url' => $posterPath,
            'duration' => $duration,
            'width' => $validated['width'] ?? $meta['width'],
            'height' => $validated['height'] ?? $meta['height'],
            'file_size' => $meta['file_size'] ?? $videoFile->getSize(),
            'likes_count' => 0,
            'status' => MomentStatus::Draft,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'data' => (new MomentResource($moment->load(['reciter', 'surah'])))->resolve(),
        ], 201);
    }

    public function show(Moment $moment): MomentResource
    {
        $this->authorize('view', $moment);

        return new MomentResource($moment->load(['reciter', 'surah']));
    }

    public function submit(Request $request, Moment $moment): JsonResponse
    {
        $this->authorize('submit', $moment);

        $moment->update([
            'status' => MomentStatus::PendingReview,
            'submitted_at' => now(),
        ]);

        return response()->json([
            'data' => (new MomentResource($moment->fresh()->load(['reciter', 'surah'])))->resolve(),
        ]);
    }

    public function approve(Request $request, Moment $moment): JsonResponse
    {
        $this->authorize('review', $moment);

        $moment->update([
            'status' => MomentStatus::Approved,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return response()->json([
            'data' => (new MomentResource($moment->fresh()->load(['reciter', 'surah'])))->resolve(),
        ]);
    }

    public function reject(Request $request, Moment $moment): JsonResponse
    {
        $this->authorize('review', $moment);

        $moment->update([
            'status' => MomentStatus::Rejected,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return response()->json([
            'data' => (new MomentResource($moment->fresh()->load(['reciter', 'surah'])))->resolve(),
        ]);
    }

    public function destroy(Moment $moment): JsonResponse
    {
        $this->authorize('delete', $moment);

        foreach ([$moment->video_url, $moment->poster_url] as $path) {
            if (filled($path)) {
                Storage::disk('r2')->delete($path);
            }
        }

        $moment->delete();

        return response()->json([
            'data' => ['message' => 'Moment deleted'],
        ]);
    }
}
