<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\RecitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Staff\StaffRecitationResource;
use App\Http\Resources\Staff\StaffReviewNoteResource;
use App\Models\Recitation;
use App\Models\Reciter;
use App\Services\RecitationReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class RecitationController extends Controller
{
    public function __construct(
        private readonly RecitationReviewService $reviews,
    ) {}

    public function indexForReciter(Request $request, Reciter $reciter): AnonymousResourceCollection
    {
        $this->authorize('view', $reciter);

        $query = $reciter->recitations()
            ->with(['surah', 'reviewNotes.user'])
            ->orderBy('surah_id');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return StaffRecitationResource::collection($query->get());
    }

    public function store(Request $request, Reciter $reciter): JsonResponse
    {
        $this->authorize('view', $reciter);
        $this->authorize('create', Recitation::class);

        $validated = $request->validate([
            'surah_id' => [
                'required',
                'integer',
                'exists:surahs,id',
                Rule::unique('recitations', 'surah_id')->where('reciter_id', $reciter->id),
            ],
            'audio' => ['required', 'file', 'max:204800', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/webm,audio/mp4,audio/m4a,audio/x-m4a,audio/ogg,application/octet-stream'],
            'submit' => ['sometimes', 'boolean'],
        ]);

        $audio = $this->reviews->storeAudio($request->file('audio'));

        $recitation = Recitation::query()->create([
            'reciter_id' => $reciter->id,
            'surah_id' => $validated['surah_id'],
            'audio_url' => $audio['audio_url'],
            'duration' => $audio['duration'],
            'file_size' => $audio['file_size'],
            'status' => RecitationStatus::Draft,
            'created_by' => $request->user()->id,
        ]);

        if ($request->boolean('submit')) {
            $this->authorize('submit', $recitation);
            $recitation = $this->reviews->submit($recitation);
        }

        return (new StaffRecitationResource($recitation->load(['surah', 'reciter', 'reviewNotes'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Recitation $recitation): StaffRecitationResource
    {
        $this->authorize('view', $recitation);

        $recitation->load(['surah', 'reciter', 'reviewNotes.user', 'reviewer']);

        return new StaffRecitationResource($recitation);
    }

    public function update(Request $request, Recitation $recitation): StaffRecitationResource
    {
        $this->authorize('update', $recitation);

        $validated = $request->validate([
            'surah_id' => [
                'sometimes',
                'integer',
                'exists:surahs,id',
                Rule::unique('recitations', 'surah_id')
                    ->where('reciter_id', $recitation->reciter_id)
                    ->ignore($recitation->id),
            ],
        ]);

        $recitation->update($validated);

        return new StaffRecitationResource($recitation->fresh(['surah', 'reciter', 'reviewNotes.user']));
    }

    public function submit(Recitation $recitation): StaffRecitationResource
    {
        $this->authorize('submit', $recitation);

        if (blank($recitation->audio_url)) {
            abort(422, 'Audio file is required before submitting.');
        }

        $recitation = $this->reviews->submit($recitation);

        return new StaffRecitationResource($recitation->load(['surah', 'reciter', 'reviewNotes.user']));
    }

    public function replaceAudio(Request $request, Recitation $recitation): StaffRecitationResource
    {
        $this->authorize('update', $recitation);

        $request->validate([
            'audio' => ['required', 'file', 'max:204800', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/webm,audio/mp4,audio/m4a,audio/x-m4a,audio/ogg,application/octet-stream'],
            'submit' => ['sometimes', 'boolean'],
        ]);

        $audio = $this->reviews->storeAudio($request->file('audio'), $recitation->audio_url);

        $recitation->update([
            'audio_url' => $audio['audio_url'],
            'duration' => $audio['duration'],
            'file_size' => $audio['file_size'],
            'status' => RecitationStatus::Draft,
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);

        if ($request->boolean('submit')) {
            $this->authorize('submit', $recitation->fresh());
            $recitation = $this->reviews->submit($recitation->fresh());
        } else {
            $recitation = $recitation->fresh(['surah', 'reciter', 'reviewNotes.user']);
        }

        return new StaffRecitationResource($recitation->load(['surah', 'reciter', 'reviewNotes.user']));
    }

    public function reviewNotes(Recitation $recitation): AnonymousResourceCollection
    {
        $this->authorize('view', $recitation);

        return StaffReviewNoteResource::collection(
            $recitation->reviewNotes()->with('user')->latest()->get()
        );
    }
}
