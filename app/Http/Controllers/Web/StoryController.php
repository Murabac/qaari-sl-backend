<?php

namespace App\Http\Controllers\Web;

use App\Enums\StoryLeaderTier;
use App\Http\Controllers\Controller;
use App\Models\StoryLeader;
use App\Models\StorySetting;
use App\Models\StoryTeamMember;
use App\Support\MediaUrl;
use Illuminate\View\View;

class StoryController extends Controller
{
    public function __invoke(): View
    {
        $settings = StorySetting::current();

        $leaders = StoryLeader::query()
            ->active()
            ->ordered()
            ->get()
            ->groupBy(fn (StoryLeader $leader) => $leader->tier->value);

        $mapLeaders = function ($items) {
            return collect($items)->map(fn (StoryLeader $leader) => [
                'leader' => $leader,
                'photo_url' => MediaUrl::temporary('r2', $leader->photo_url),
            ]);
        };

        $team = StoryTeamMember::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (StoryTeamMember $member) => [
                'member' => $member,
                'photo_url' => MediaUrl::temporary('r2', $member->photo_url),
            ]);

        return view('story', [
            'settings' => $settings,
            'president' => $mapLeaders($leaders->get(StoryLeaderTier::President->value, collect())),
            'ministers' => $mapLeaders($leaders->get(StoryLeaderTier::Minister->value, collect())),
            'board' => $mapLeaders($leaders->get(StoryLeaderTier::Board->value, collect())),
            'team' => $team,
        ]);
    }
}
