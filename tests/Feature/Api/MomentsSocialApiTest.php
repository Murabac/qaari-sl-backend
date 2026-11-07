<?php

namespace Tests\Feature\Api;

use App\Enums\MomentStatus;
use App\Models\Moment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesCatalogData;
use Tests\TestCase;

class MomentsSocialApiTest extends TestCase
{
    use CreatesCatalogData;
    use RefreshDatabase;

    public function test_trending_orders_by_likes_and_hashtags_are_parsed(): void
    {
        $quiet = $this->makeMoment(['likes_count' => 1, 'caption' => 'Quiet']);
        $loud = $this->makeMoment(['likes_count' => 9, 'caption' => 'Reading in the mosque #mosque']);

        $this->getJson('/api/v1/moments?filter=trending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $loud->id)
            ->assertJsonPath('data.0.hashtags.0', 'mosque')
            ->assertJsonPath('data.1.id', $quiet->id);
    }

    public function test_listener_can_follow_save_and_comment(): void
    {
        $user = $this->makeUser();
        $reciter = $this->makeReciter();
        $moment = $this->makeMoment([
            'reciter_id' => $reciter->id,
            'caption' => 'Reading in the mosque',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/reciters/'.$reciter->id.'/follow')
            ->assertOk()
            ->assertJsonPath('data.followed', true);

        $this->getJson('/api/v1/moments?filter=following')
            ->assertOk()
            ->assertJsonPath('data.0.id', $moment->id)
            ->assertJsonPath('data.0.followed_reciter', true);

        $this->postJson('/api/v1/moments/'.$moment->id.'/save')
            ->assertCreated()
            ->assertJsonPath('data.saved', true)
            ->assertJsonPath('data.saves_count', 1);

        $this->postJson('/api/v1/moments/'.$moment->id.'/comments', [
            'body' => 'Beautiful.',
        ])->assertCreated()
            ->assertJsonPath('data.body', 'Beautiful.')
            ->assertJsonPath('data.mine', true);

        $this->assertDatabaseHas('moments', [
            'id' => $moment->id,
            'comments_count' => 1,
        ]);

        $commentId = $moment->comments()->value('id');

        $this->deleteJson('/api/v1/moments/'.$moment->id.'/comments/'.$commentId)
            ->assertOk();

        $this->assertDatabaseHas('moments', [
            'id' => $moment->id,
            'comments_count' => 0,
        ]);
    }

    public function test_following_filter_requires_auth(): void
    {
        $this->getJson('/api/v1/moments?filter=following')->assertUnauthorized();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeMoment(array $overrides = []): Moment
    {
        return Moment::query()->create(array_merge([
            'title' => 'Reading in the mosque',
            'caption' => 'Reading in the mosque',
            'video_url' => 'moments/videos/test.mp4',
            'duration' => 8,
            'width' => 1080,
            'height' => 1920,
            'status' => MomentStatus::Approved,
        ], $overrides));
    }
}
