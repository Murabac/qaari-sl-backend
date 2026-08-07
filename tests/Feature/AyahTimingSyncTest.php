<?php

namespace Tests\Feature;

use App\Enums\RecitationStatus;
use App\Enums\SyncStatus;
use App\Models\Ayah;
use App\Models\RecitationAyahTiming;
use App\Services\AyahTimingSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\Concerns\CreatesCatalogData;
use Tests\TestCase;

class AyahTimingSyncTest extends TestCase
{
    use CreatesCatalogData;
    use RefreshDatabase;

    public function test_toolchain_check_command_runs(): void
    {
        $this->artisan('ayah:sync --check')->assertExitCode(0);
    }

    public function test_sync_persists_timings_from_aligner_output(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->put('recitations/audio/demo.mp3', 'fake-bytes');

        $surah = $this->makeSurah(['number' => 1, 'verse_count' => 3]);
        Ayah::query()->create(['surah_id' => $surah->id, 'number' => 1, 'text_uthmani' => 'ا']);
        Ayah::query()->create(['surah_id' => $surah->id, 'number' => 2, 'text_uthmani' => 'بب']);
        Ayah::query()->create(['surah_id' => $surah->id, 'number' => 3, 'text_uthmani' => 'ججج']);

        $recitation = $this->makeRecitation(
            surah: $surah,
            status: RecitationStatus::Approved,
            overrides: ['audio_url' => 'recitations/audio/demo.mp3', 'duration' => 30],
        );

        $local = storage_path('app/tmp/test-audio.mp3');
        if (! is_dir(dirname($local))) {
            mkdir(dirname($local), 0755, true);
        }
        file_put_contents($local, 'x');

        /** @var AyahTimingSyncService&\Mockery\MockInterface $service */
        $service = Mockery::mock(AyahTimingSyncService::class)->makePartial();
        $service->shouldReceive('materializeAudio')->once()->andReturn($local);
        $service->shouldReceive('runAligner')->once()->andReturn([
            'method' => 'test_fixture',
            'duration_ms' => 30000,
            'timings' => [
                ['ayah' => 1, 'start_ms' => 0, 'end_ms' => 10000],
                ['ayah' => 2, 'start_ms' => 10000, 'end_ms' => 20000],
                ['ayah' => 3, 'start_ms' => 20000, 'end_ms' => 30000],
            ],
        ]);

        $synced = $service->sync($recitation->fresh('surah'));

        @unlink($local);

        $this->assertSame(SyncStatus::Synced, $synced->sync_status);
        $this->assertSame('test_fixture', $synced->sync_method);
        $this->assertSame(3, RecitationAyahTiming::query()->where('recitation_id', $recitation->id)->count());
        $this->assertSame([0.0, 10.0, 20.0], $synced->fresh('ayahTimings')->ayahStartSeconds());
    }

    public function test_manual_timings_can_be_saved(): void
    {
        $surah = $this->makeSurah(['number' => 108, 'verse_count' => 3]);
        Ayah::query()->create(['surah_id' => $surah->id, 'number' => 1, 'text_uthmani' => 'ا']);
        Ayah::query()->create(['surah_id' => $surah->id, 'number' => 2, 'text_uthmani' => 'ب']);
        Ayah::query()->create(['surah_id' => $surah->id, 'number' => 3, 'text_uthmani' => 'ج']);

        $recitation = $this->makeRecitation(
            surah: $surah,
            status: RecitationStatus::Approved,
            overrides: ['duration' => 30],
        );

        $synced = app(AyahTimingSyncService::class)->saveManualTimings($recitation->fresh('surah'), [0, 8.5, 19.25], 2);

        $this->assertSame(SyncStatus::Synced, $synced->sync_status);
        $this->assertSame('manual', $synced->sync_method);
        $this->assertSame(2, $synced->manual_sync_ayah);
        $this->assertSame([0.0, 8.5, 19.25], $synced->fresh('ayahTimings')->ayahStartSeconds());
        $this->assertSame(
            30000,
            (int) RecitationAyahTiming::query()
                ->where('recitation_id', $recitation->id)
                ->where('ayah_number', 3)
                ->value('end_ms'),
        );

        $this->expectException(\RuntimeException::class);
        app(AyahTimingSyncService::class)->sync($synced->fresh('surah'));
    }

    public function test_follow_along_receives_synced_starts(): void
    {
        $surah = $this->makeSurah(['number' => 112, 'verse_count' => 2]);
        Ayah::query()->create(['surah_id' => $surah->id, 'number' => 1, 'text_uthmani' => 'قُلْ']);
        Ayah::query()->create(['surah_id' => $surah->id, 'number' => 2, 'text_uthmani' => 'ٱللَّهُ']);
        $recitation = $this->makeRecitation(surah: $surah, status: RecitationStatus::Approved);
        $recitation->update([
            'sync_status' => SyncStatus::Synced,
            'synced_at' => now(),
            'sync_method' => 'test',
        ]);
        RecitationAyahTiming::query()->create([
            'recitation_id' => $recitation->id,
            'ayah_number' => 1,
            'start_ms' => 0,
            'end_ms' => 5000,
        ]);
        RecitationAyahTiming::query()->create([
            'recitation_id' => $recitation->id,
            'ayah_number' => 2,
            'start_ms' => 5000,
            'end_ms' => 10000,
        ]);

        $this->get('/listen/'.$recitation->id)
            ->assertOk()
            ->assertSee('ayahStarts', false)
            ->assertSee('5', false);
    }
}
