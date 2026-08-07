<?php

namespace Tests\Feature\Api;

use App\Enums\StoryLeaderTier;
use App\Models\StoryLeader;
use App\Models\StorySetting;
use App\Models\StoryTeamMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_story_endpoint_returns_mission_leaders_and_team(): void
    {
        StorySetting::current()->update([
            'hero_mission' => 'Mission text for the archive.',
            'closing_note' => 'Closing gratitude note.',
        ]);

        StoryLeader::query()->create([
            'name' => 'Patron One',
            'title' => 'Patron',
            'tier' => StoryLeaderTier::President,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        StoryTeamMember::query()->create([
            'name' => 'Team Member',
            'role' => 'Producer',
            'description' => 'Builds the archive.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/story')
            ->assertOk()
            ->assertJsonPath('data.hero_mission', 'Mission text for the archive.')
            ->assertJsonPath('data.closing_note', 'Closing gratitude note.')
            ->assertJsonPath('data.president.0.name', 'Patron One')
            ->assertJsonPath('data.team.0.name', 'Team Member');
    }
}
