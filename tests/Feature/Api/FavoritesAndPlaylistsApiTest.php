<?php

namespace Tests\Feature\Api;

use App\Enums\RecitationStatus;
use App\Models\Playlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesCatalogData;
use Tests\TestCase;

class FavoritesAndPlaylistsApiTest extends TestCase
{
    use CreatesCatalogData;
    use RefreshDatabase;

    public function test_can_favorite_approved_recitation(): void
    {
        $user = $this->makeUser();
        $recitation = $this->makeRecitation(status: RecitationStatus::Approved);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/favorites', [
            'recitation_id' => $recitation->id,
        ])->assertCreated();

        $this->getJson('/api/v1/favorites')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recitation->id);

        $this->deleteJson('/api/v1/favorites/'.$recitation->id)
            ->assertOk();

        $this->getJson('/api/v1/favorites')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_cannot_favorite_draft_recitation(): void
    {
        $user = $this->makeUser();
        $draft = $this->makeRecitation(status: RecitationStatus::Draft);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/favorites', [
            'recitation_id' => $draft->id,
        ])->assertNotFound();
    }

    public function test_playlist_crud_and_ownership(): void
    {
        $owner = $this->makeUser(['email' => 'owner@example.com']);
        $other = $this->makeUser(['email' => 'other@example.com']);
        $recitation = $this->makeRecitation(status: RecitationStatus::Approved);

        Sanctum::actingAs($owner);

        $create = $this->postJson('/api/v1/playlists', ['name' => 'Morning']);
        $create->assertCreated()->assertJsonPath('data.name', 'Morning');
        $playlistId = $create->json('data.id');

        $this->postJson("/api/v1/playlists/{$playlistId}/items", [
            'recitation_id' => $recitation->id,
        ])->assertCreated();

        $this->getJson("/api/v1/playlists/{$playlistId}")
            ->assertOk()
            ->assertJsonCount(1, 'data.items');

        Sanctum::actingAs($other);

        $this->getJson("/api/v1/playlists/{$playlistId}")->assertNotFound();
        $this->putJson("/api/v1/playlists/{$playlistId}", ['name' => 'Hacked'])->assertNotFound();

        Sanctum::actingAs($owner);

        $this->putJson("/api/v1/playlists/{$playlistId}", ['name' => 'Evening'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Evening');

        $this->deleteJson("/api/v1/playlists/{$playlistId}")->assertOk();

        $this->assertDatabaseMissing('playlists', ['id' => $playlistId]);
    }

    public function test_playlist_reorder_requires_all_items(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $playlist = Playlist::query()->create([
            'user_id' => $user->id,
            'name' => 'Order test',
        ]);

        $a = $this->makeRecitation(status: RecitationStatus::Approved);
        $b = $this->makeRecitation(
            $this->makeReciter(['name_english' => 'Second']),
            $this->makeSurah(['number' => 5]),
            RecitationStatus::Approved,
        );

        $itemA = $playlist->items()->create(['recitation_id' => $a->id, 'sort_order' => 1]);
        $itemB = $playlist->items()->create(['recitation_id' => $b->id, 'sort_order' => 2]);

        $this->putJson("/api/v1/playlists/{$playlist->id}/reorder", [
            'item_ids' => [$itemB->id, $itemA->id],
        ])->assertOk();

        $this->assertDatabaseHas('playlist_items', ['id' => $itemB->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('playlist_items', ['id' => $itemA->id, 'sort_order' => 2]);
    }
}
