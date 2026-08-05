<?php

namespace Tests\Feature\Api;

use App\Enums\RecitationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogData;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use CreatesCatalogData;
    use RefreshDatabase;

    public function test_lists_only_reciters_with_approved_recitations(): void
    {
        $visible = $this->makeReciter(['name_english' => 'Visible Reciter']);
        $hidden = $this->makeReciter(['name_english' => 'Hidden Reciter']);

        $this->makeRecitation($visible, status: RecitationStatus::Approved);
        $this->makeRecitation($hidden, status: RecitationStatus::Draft);

        $response = $this->getJson('/api/v1/reciters');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name_english', 'Visible Reciter');
    }

    public function test_reciter_detail_includes_approved_recitations_only(): void
    {
        $reciter = $this->makeReciter();
        $approved = $this->makeRecitation($reciter, status: RecitationStatus::Approved);
        $this->makeRecitation($reciter, $this->makeSurah(['number' => 2]), RecitationStatus::PendingReview);

        $response = $this->getJson('/api/v1/reciters/'.$reciter->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data.recitations')
            ->assertJsonPath('data.recitations.0.id', $approved->id);
    }

    public function test_reciter_without_approved_recitations_returns_404(): void
    {
        $reciter = $this->makeReciter();
        $this->makeRecitation($reciter, status: RecitationStatus::Draft);

        $this->getJson('/api/v1/reciters/'.$reciter->id)->assertNotFound();
    }

    public function test_lists_surahs(): void
    {
        $this->makeSurah(['number' => 1, 'name_english' => 'Al-Fatihah']);
        $this->makeSurah(['number' => 2, 'name_english' => 'Al-Baqarah']);

        $this->getJson('/api/v1/surahs')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_lists_approved_recitations_and_hides_others(): void
    {
        $approved = $this->makeRecitation(status: RecitationStatus::Approved);
        $this->makeRecitation(
            $this->makeReciter(['name_english' => 'Other']),
            $this->makeSurah(['number' => 3]),
            RecitationStatus::Rejected,
        );

        $response = $this->getJson('/api/v1/recitations');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approved->id);
    }

    public function test_non_approved_recitation_detail_returns_404(): void
    {
        $draft = $this->makeRecitation(status: RecitationStatus::Draft);

        $this->getJson('/api/v1/recitations/'.$draft->id)->assertNotFound();
    }

    public function test_search_returns_matching_catalog(): void
    {
        $reciter = $this->makeReciter(['name_english' => 'Abdullah Hargeisa']);
        $surah = $this->makeSurah(['number' => 36, 'name_english' => 'Ya-Sin']);
        $this->makeRecitation($reciter, $surah, RecitationStatus::Approved);

        $response = $this->getJson('/api/v1/search?q=Hargeisa');

        $response->assertOk()
            ->assertJsonCount(1, 'data.reciters')
            ->assertJsonPath('data.reciters.0.name_english', 'Abdullah Hargeisa');
    }
}
