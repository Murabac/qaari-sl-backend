<?php

namespace Tests\Feature\Api;

use App\Enums\MomentStatus;
use App\Enums\StaffRole;
use App\Filament\Resources\Moments\MomentResource;
use App\Models\Moment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesCatalogData;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class MomentsSocialApiTest extends TestCase
{
    use CreatesCatalogData;
    use CreatesStaffUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['moments.enabled' => true]);
    }

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

    public function test_enabled_flag_keeps_the_public_list_and_a_clip(): void
    {
        $moment = $this->makeMoment();

        $this->getJson('/api/v1/moments')
            ->assertOk()
            ->assertJsonPath('data.0.id', $moment->id)
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/v1/moments/'.$moment->id)
            ->assertOk()
            ->assertJsonPath('data.id', $moment->id);
    }

    public function test_disabled_flag_returns_an_empty_list_and_hides_the_rest(): void
    {
        config(['moments.enabled' => false]);

        $moment = $this->makeMoment();
        $listener = $this->makeUser();
        $admin = $this->makeStaffUser(StaffRole::Admin);

        $this->getJson('/api/v1/moments?filter=following')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
            ]);

        $this->getJson('/api/v1/moments/'.$moment->id)->assertNotFound();
        $this->getJson('/api/v1/moments/'.$moment->id.'/comments')->assertNotFound();

        Sanctum::actingAs($listener);
        $this->postJson('/api/v1/moments/'.$moment->id.'/like')->assertNotFound();
        $this->deleteJson('/api/v1/moments/'.$moment->id.'/like')->assertNotFound();
        $this->postJson('/api/v1/moments/'.$moment->id.'/save')->assertNotFound();
        $this->deleteJson('/api/v1/moments/'.$moment->id.'/save')->assertNotFound();
        $this->postJson('/api/v1/moments/'.$moment->id.'/comments', [
            'body' => 'Hidden.',
        ])->assertNotFound();

        Sanctum::actingAs($admin);
        $this->getJson('/api/staff/moments')->assertNotFound();
        $this->getJson('/api/staff/moments/'.$moment->id)->assertNotFound();
        $this->postJson('/api/staff/moments/'.$moment->id.'/approve')->assertNotFound();

        $this->actingAs($admin, 'web');
        $this->assertFalse(MomentResource::shouldRegisterNavigation());
        $this->assertFalse(MomentResource::canAccess());
        $this->get('/admin/moments')->assertForbidden();
    }

    public function test_enabled_flag_shows_moments_in_filament(): void
    {
        $admin = $this->makeStaffUser(StaffRole::Admin);

        $this->actingAs($admin, 'web');

        $this->assertTrue(MomentResource::shouldRegisterNavigation());
        $this->assertTrue(MomentResource::canAccess());
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
