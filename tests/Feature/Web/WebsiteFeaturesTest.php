<?php

namespace Tests\Feature\Web;

use App\Enums\RecitationStatus;
use App\Models\Ayah;
use App\Models\Favorite;
use App\Models\Playlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogData;
use Tests\TestCase;

class WebsiteFeaturesTest extends TestCase
{
    use CreatesCatalogData;
    use RefreshDatabase;

    public function test_guest_can_view_login_and_register(): void
    {
        $this->get('/login')->assertOk()->assertSee('Log in', false);
        $this->get('/register')->assertOk()->assertSee('Sign up', false);
    }

    public function test_user_can_register_and_access_library(): void
    {
        $this->post('/register', [
            'name' => 'Listener One',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('library.favorites'));

        $this->assertAuthenticated();
        $this->get('/library/favorites')->assertOk()->assertSee('Favorites', false);
    }

    public function test_favorites_require_auth_and_can_be_toggled(): void
    {
        $user = $this->makeUser();
        $recitation = $this->makeRecitation(status: RecitationStatus::Approved);

        $this->post('/library/favorites', ['recitation_id' => $recitation->id])
            ->assertRedirect('/login');

        $this->actingAs($user)
            ->post('/library/favorites', ['recitation_id' => $recitation->id])
            ->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'recitation_id' => $recitation->id,
        ]);

        $this->actingAs($user)
            ->get('/library/favorites')
            ->assertOk();

        $this->actingAs($user)
            ->delete('/library/favorites/'.$recitation->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'recitation_id' => $recitation->id,
        ]);
    }

    public function test_playlists_crud_for_owner(): void
    {
        $user = $this->makeUser(['email' => 'owner@example.com']);
        $other = $this->makeUser(['email' => 'other@example.com']);
        $recitation = $this->makeRecitation(status: RecitationStatus::Approved);

        $this->actingAs($user)
            ->post('/library/playlists', ['name' => 'Morning'])
            ->assertRedirect();

        $playlist = Playlist::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post('/library/playlists/'.$playlist->id.'/items', ['recitation_id' => $recitation->id])
            ->assertRedirect();

        $this->actingAs($user)
            ->get('/library/playlists/'.$playlist->id)
            ->assertOk()
            ->assertSee('Morning', false);

        $this->actingAs($other)
            ->get('/library/playlists/'.$playlist->id)
            ->assertNotFound();
    }

    public function test_follow_along_page_shows_ayahs_for_approved_recitation(): void
    {
        $surah = $this->makeSurah(['number' => 1, 'verse_count' => 2]);
        $recitation = $this->makeRecitation(surah: $surah, status: RecitationStatus::Approved);

        Ayah::query()->create([
            'surah_id' => $surah->id,
            'number' => 1,
            'text_uthmani' => 'بِسْمِ ٱللَّهِ',
        ]);
        Ayah::query()->create([
            'surah_id' => $surah->id,
            'number' => 2,
            'text_uthmani' => 'ٱلْحَمْدُ لِلَّهِ',
        ]);

        $this->get('/listen/'.$recitation->id)
            ->assertOk()
            ->assertSee('Follow along', false)
            ->assertSee('بِسْمِ ٱللَّهِ', false)
            ->assertSee('og:title', false);

        $draft = $this->makeRecitation(
            reciter: $recitation->reciter,
            surah: $this->makeSurah(['number' => 2, 'verse_count' => 1]),
            status: RecitationStatus::Draft,
        );

        $this->get('/listen/'.$draft->id)->assertNotFound();
    }

    public function test_share_deep_link_query_on_reciter_page(): void
    {
        $reciter = $this->makeReciter(['name_english' => 'Share Reciter']);
        $recitation = $this->makeRecitation($reciter, status: RecitationStatus::Approved);

        $this->get('/reciters/'.$reciter->id.'?play='.$recitation->id)
            ->assertOk()
            ->assertSee('data-recitation-id="'.$recitation->id.'"', false)
            ->assertSee('Share Reciter', false);
    }

    public function test_favorite_model_helper_still_works(): void
    {
        $user = $this->makeUser(['email' => 'fav@example.com']);
        $recitation = $this->makeRecitation(status: RecitationStatus::Approved);

        Favorite::query()->create([
            'user_id' => $user->id,
            'recitation_id' => $recitation->id,
        ]);

        $this->assertTrue($user->favorites()->where('recitation_id', $recitation->id)->exists());
    }
}
