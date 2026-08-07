<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Http\Resources\SurahResource;
use App\Models\Surah;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SurahController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $surahs = Surah::query()->orderBy('number')->get();

        return SurahResource::collection($surahs);
    }
}
