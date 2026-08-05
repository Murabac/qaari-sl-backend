<?php

namespace Tests\Feature\Web;

use App\Enums\RecitationStatus;
use App\Models\Partner;
use App\Models\StorySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogData;
use Tests\TestCase;

class WebsitePagesTest extends TestCase
{
    use CreatesCatalogData;
    use RefreshDatabase;

    public function test_home_and_reciters_pages_render(): void
    {
        $reciter = $this->makeReciter(['name_english' => 'Website Reciter']);
        $this->makeRecitation($reciter, status: RecitationStatus::Approved);

        $this->get('/')->assertOk()->assertSee('Xulka Quraa&#039;da', false);
        $this->get('/reciters')->assertOk()->assertSee('Website Reciter', false);
        $this->get('/reciters/'.$reciter->id)->assertOk()->assertSee('Website Reciter', false);
        $this->get('/story')->assertOk()->assertSee('The Story So Far', false);
    }

    public function test_locale_switch_sets_session_and_rtl(): void
    {
        $this->from('/');
        $this->get('/locale/ar')->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('تصفح القراء', false);
    }

    public function test_draft_only_reciter_is_hidden_from_site(): void
    {
        $reciter = $this->makeReciter(['name_english' => 'Hidden Draft Reciter']);
        $this->makeRecitation($reciter, status: RecitationStatus::Draft);

        $this->get('/reciters')->assertOk()->assertDontSee('Hidden Draft Reciter');
        $this->get('/reciters/'.$reciter->id)->assertNotFound();
    }

    public function test_partners_section_can_be_disabled(): void
    {
        StorySetting::current()->update(['partners_section_enabled' => false]);

        Partner::query()->create([
            'name' => 'Hidden Partner Org',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get('/')->assertOk()->assertDontSee('Hidden Partner Org');
    }

    public function test_partners_section_shows_when_enabled(): void
    {
        StorySetting::current()->update(['partners_section_enabled' => true]);

        Partner::query()->create([
            'name' => 'Visible Partner Org',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Our Partners', false)
            ->assertSee('Visible Partner Org', false);
    }
}
