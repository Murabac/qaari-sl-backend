<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StoryLeaderTier;
use App\Http\Controllers\Controller;
use App\Models\StoryLeader;
use App\Models\StorySetting;
use App\Models\StoryTeamMember;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;

class StoryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = StorySetting::current();

        $leaders = StoryLeader::query()
            ->active()
            ->ordered()
            ->get()
            ->groupBy(fn (StoryLeader $leader) => $leader->tier->value);

        $mapLeaders = function ($items) {
            return collect($items)->map(fn (StoryLeader $leader) => [
                'id' => $leader->id,
                'name' => $leader->name,
                'title' => $leader->title,
                'tier' => $leader->tier->value,
                'tier_label' => $leader->tier->label(),
                'photo_url' => MediaUrl::temporary('r2', $leader->photo_url),
            ])->values()->all();
        };

        $team = StoryTeamMember::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (StoryTeamMember $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => $member->role,
                'description' => $member->description,
                'photo_url' => MediaUrl::temporary('r2', $member->photo_url),
            ])
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'hero_mission' => $settings->hero_mission,
                'closing_note' => $settings->closing_note,
                'president' => $mapLeaders($leaders->get(StoryLeaderTier::President->value, collect())),
                'ministers' => $mapLeaders($leaders->get(StoryLeaderTier::Minister->value, collect())),
                'board' => $mapLeaders($leaders->get(StoryLeaderTier::Board->value, collect())),
                'team' => $team,
            ],
        ]);
    }
}
