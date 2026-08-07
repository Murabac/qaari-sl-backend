<?php

namespace Tests\Feature\Api;

use App\Enums\RecitationStatus;
use App\Enums\StaffRole;
use App\Models\Ayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesCatalogData;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class StaffApiTest extends TestCase
{
    use CreatesCatalogData;
    use CreatesStaffUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('r2');
    }

    public function test_staff_login_requires_staff_role(): void
    {
        $this->makeUser(['email' => 'listener@example.com']);

        $this->postJson('/api/staff/login', [
            'email' => 'listener@example.com',
            'password' => 'password',
        ])->assertUnprocessable();
    }

    public function test_production_can_login_and_see_dashboard(): void
    {
        $this->makeStaffUser(StaffRole::Production);

        $login = $this->postJson('/api/staff/login', [
            'email' => 'production@qaarisl.com',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.user.email', 'production@qaarisl.com')
            ->assertJsonPath('data.user.roles.0', 'production');

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/staff/me')
            ->assertOk()
            ->assertJsonPath('data.is_production', true);

        $this->withToken($token)
            ->getJson('/api/staff/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['counts' => ['drafts', 'rejected', 'pending_review']]]);
    }

    public function test_production_reciter_and_recitation_flow(): void
    {
        $production = $this->makeStaffUser(StaffRole::Production);
        $surah = $this->makeSurah(['number' => 1]);
        Sanctum::actingAs($production);

        $createReciter = $this->post('/api/staff/reciters', [
            'name_english' => 'Field Reciter',
            'name_somali' => 'Qaari',
            'name_arabic' => 'قارئ',
            'region' => 'Hargeisa',
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.name_english', 'Field Reciter');

        $reciterId = $createReciter->json('data.id');

        $createRecitation = $this->post('/api/staff/reciters/'.$reciterId.'/recitations', [
            'surah_id' => $surah->id,
            'audio' => UploadedFile::fake()->create('fatiha.mp3', 200, 'audio/mpeg'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $recitationId = $createRecitation->json('data.id');

        $this->postJson('/api/staff/recitations/'.$recitationId.'/submit')
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_review');

        $other = $this->makeStaffUser(StaffRole::Production, [
            'email' => 'other-production@qaarisl.com',
        ]);

        $this->flushSession();
        Auth::forgetGuards();
        Sanctum::actingAs($other);

        $this->getJson('/api/staff/reciters/'.$reciterId)
            ->assertForbidden();
    }

    public function test_admin_can_approve_and_reject_with_voice_note(): void
    {
        $production = $this->makeStaffUser(StaffRole::Production);
        $admin = $this->makeStaffUser(StaffRole::Admin);
        $reciter = $this->makeReciter(['created_by' => $production->id]);
        $surah = $this->makeSurah(['number' => 2]);

        $pending = $this->makeRecitation($reciter, $surah, RecitationStatus::PendingReview, [
            'created_by' => $production->id,
            'submitted_at' => now(),
        ]);

        $adminToken = $admin->createToken('staff')->plainTextToken;

        $this->withToken($adminToken)
            ->getJson('/api/staff/reviews?status=pending_review')
            ->assertOk()
            ->assertJsonPath('data.0.id', $pending->id);

        $this->withToken($adminToken)
            ->postJson('/api/staff/recitations/'.$pending->id.'/approve')
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $surah3 = $this->makeSurah(['number' => 3]);
        $toReject = $this->makeRecitation($reciter, $surah3, RecitationStatus::PendingReview, [
            'created_by' => $production->id,
            'submitted_at' => now(),
        ]);

        $this->withToken($adminToken)
            ->post('/api/staff/recitations/'.$toReject->id.'/reject', [
                'caption' => 'Please re-record ayah 5',
                'voice_note' => UploadedFile::fake()->create('note.m4a', 40, 'audio/mp4'),
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('recitation_review_notes', [
            'recitation_id' => $toReject->id,
            'caption' => 'Please re-record ayah 5',
        ]);

        $this->flushSession();
        Auth::forgetGuards();
        Sanctum::actingAs($production);

        $this->getJson('/api/staff/recitations/'.$toReject->id.'/review-notes')
            ->assertOk()
            ->assertJsonPath('data.0.caption', 'Please re-record ayah 5');

        $this->post('/api/staff/recitations/'.$toReject->id.'/replace-audio', [
            'audio' => UploadedFile::fake()->create('fixed.mp3', 220, 'audio/mpeg'),
            'submit' => '1',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_review');

        $this->postJson('/api/staff/recitations/'.$toReject->id.'/approve')
            ->assertForbidden();
    }

    public function test_admin_can_save_manual_ayah_sync_production_cannot(): void
    {
        $production = $this->makeStaffUser(StaffRole::Production);
        $admin = $this->makeStaffUser(StaffRole::Admin);
        $surah = $this->makeSurah(['number' => 108, 'verse_count' => 3]);
        Ayah::query()->create(['surah_id' => $surah->id, 'number' => 1, 'text_uthmani' => 'ا']);
        Ayah::query()->create(['surah_id' => $surah->id, 'number' => 2, 'text_uthmani' => 'ب']);
        Ayah::query()->create(['surah_id' => $surah->id, 'number' => 3, 'text_uthmani' => 'ج']);

        $recitation = $this->makeRecitation($this->makeReciter(['created_by' => $production->id]), $surah, RecitationStatus::Approved, [
            'created_by' => $production->id,
            'duration' => 30,
            'audio_url' => 'recitations/audio/demo.mp3',
        ]);

        Sanctum::actingAs($production);
        $this->getJson('/api/staff/recitations/'.$recitation->id.'/ayah-sync')
            ->assertForbidden();

        $this->flushSession();
        Auth::forgetGuards();
        Sanctum::actingAs($admin);

        $this->getJson('/api/staff/recitations/'.$recitation->id.'/ayah-sync')
            ->assertOk()
            ->assertJsonPath('data.verse_count', 3)
            ->assertJsonCount(3, 'data.ayahs')
            ->assertJsonCount(3, 'data.ayah_starts');

        $this->putJson('/api/staff/recitations/'.$recitation->id.'/ayah-sync', [
            'ayah_starts' => [0, 8.5, 19.25],
            'resume_ayah' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.sync_method', 'manual')
            ->assertJsonPath('data.sync_status', 'synced')
            ->assertJsonPath('data.resume_ayah', 2)
            ->assertJsonPath('data.ayah_starts.1', 8.5);

        $this->assertDatabaseHas('recitations', [
            'id' => $recitation->id,
            'sync_method' => 'manual',
            'manual_sync_ayah' => 2,
        ]);
    }

    public function test_non_staff_token_cannot_access_staff_routes(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/staff/dashboard')
            ->assertForbidden();
    }
}
