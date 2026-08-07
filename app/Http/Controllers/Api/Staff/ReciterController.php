<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Http\Resources\Staff\StaffReciterResource;
use App\Models\Reciter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class ReciterController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Reciter::class);

        $user = $request->user();
        $query = Reciter::query()
            ->withCount('recitations')
            ->orderBy('name_english');

        if (! $user->isReviewer()) {
            $query->where('created_by', $user->id);
        }

        if ($search = $request->string('q')->toString()) {
            $query->search($search);
        }

        return StaffReciterResource::collection($query->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Reciter::class);

        $validated = $request->validate([
            'name_somali' => ['required', 'string', 'max:255'],
            'name_arabic' => ['required', 'string', 'max:255'],
            'name_english' => ['required', 'string', 'max:255'],
            'bio_somali' => ['nullable', 'string'],
            'bio_arabic' => ['nullable', 'string'],
            'bio_english' => ['nullable', 'string'],
            'region' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('reciters/photos', 'r2');
        }

        $reciter = Reciter::query()->create([
            'name_somali' => $validated['name_somali'] ?? null,
            'name_arabic' => $validated['name_arabic'] ?? null,
            'name_english' => $validated['name_english'],
            'bio_somali' => $validated['bio_somali'] ?? null,
            'bio_arabic' => $validated['bio_arabic'] ?? null,
            'bio_english' => $validated['bio_english'] ?? null,
            'region' => $validated['region'] ?? null,
            'photo_url' => $photoPath,
            'created_by' => $request->user()->id,
        ]);

        return (new StaffReciterResource($reciter->loadCount('recitations')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Reciter $reciter): StaffReciterResource
    {
        $this->authorize('view', $reciter);

        $reciter->load([
            'recitations' => fn ($q) => $q->with(['surah', 'reviewNotes.user'])->orderBy('surah_id'),
        ])->loadCount('recitations');

        return new StaffReciterResource($reciter);
    }

    public function update(Request $request, Reciter $reciter): StaffReciterResource
    {
        $this->authorize('update', $reciter);

        $validated = $request->validate([
            'name_somali' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name_arabic' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name_english' => ['sometimes', 'required', 'string', 'max:255'],
            'bio_somali' => ['sometimes', 'nullable', 'string'],
            'bio_arabic' => ['sometimes', 'nullable', 'string'],
            'bio_english' => ['sometimes', 'nullable', 'string'],
            'region' => ['sometimes', 'nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ]);

        if ($request->hasFile('photo')) {
            if (filled($reciter->photo_url)) {
                try {
                    Storage::disk('r2')->delete($reciter->photo_url);
                } catch (\Throwable) {
                    // Ignore.
                }
            }
            $validated['photo_url'] = $request->file('photo')->store('reciters/photos', 'r2');
        }

        unset($validated['photo']);
        $reciter->update($validated);

        return new StaffReciterResource($reciter->fresh()->loadCount('recitations'));
    }
}
