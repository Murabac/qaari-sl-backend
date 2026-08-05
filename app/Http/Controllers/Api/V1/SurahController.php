<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SurahResource;
use App\Models\Surah;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SurahController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $surahs = Surah::query()
            ->search($request->query('q'))
            ->orderBy('number')
            ->paginate(min((int) $request->integer('per_page', 114), 114));

        return SurahResource::collection($surahs);
    }

    public function show(Surah $surah): SurahResource
    {
        return new SurahResource($surah);
    }
}
