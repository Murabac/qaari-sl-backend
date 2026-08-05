<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecitationResource;
use App\Models\Recitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecitationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $recitations = Recitation::query()
            ->approved()
            ->with(['reciter', 'surah'])
            ->when($request->filled('reciter_id'), fn ($q) => $q->where('reciter_id', $request->integer('reciter_id')))
            ->when($request->filled('surah_id'), fn ($q) => $q->where('surah_id', $request->integer('surah_id')))
            ->orderBy('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return RecitationResource::collection($recitations);
    }

    public function show(int $recitation): RecitationResource
    {
        $record = Recitation::query()
            ->approved()
            ->with(['reciter', 'surah'])
            ->findOrFail($recitation);

        return new RecitationResource($record);
    }
}
