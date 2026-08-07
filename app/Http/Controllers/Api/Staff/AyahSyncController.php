<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Http\Resources\Staff\StaffRecitationResource;
use App\Models\Ayah;
use App\Models\Recitation;
use App\Services\AyahTimingSyncService;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class AyahSyncController extends Controller
{
    public function __construct(
        private readonly AyahTimingSyncService $sync,
    ) {}

    public function show(Recitation $recitation): JsonResponse
    {
        $this->authorize('syncAyahs', $recitation);

        $recitation->load(['ayahTimings', 'surah', 'reciter']);

        $ayahs = Ayah::query()
            ->where('surah_id', $recitation->surah_id)
            ->orderBy('number')
            ->get(['number', 'text_uthmani']);

        $verseCount = max(
            1,
            $ayahs->count() ?: (int) ($recitation->surah?->verse_count ?? 1),
        );

        $timings = $recitation->ayahTimings->sortBy('ayah_number')->values();

        if ($timings->isNotEmpty()) {
            $starts = $timings
                ->map(fn ($t) => round($t->start_ms / 1000, 3))
                ->values()
                ->all();
        } else {
            $durationSeconds = max(1, (int) ($recitation->duration ?? 0));
            $step = $durationSeconds / $verseCount;
            $starts = [];
            for ($i = 0; $i < $verseCount; $i++) {
                $starts[] = round($i * $step, 3);
            }
        }

        // Pad / trim to verse count if needed.
        if (count($starts) < $verseCount) {
            $durationSeconds = max(1, (int) ($recitation->duration ?? 0));
            $step = $durationSeconds / $verseCount;
            while (count($starts) < $verseCount) {
                $starts[] = round(count($starts) * $step, 3);
            }
        }
        $starts = array_slice($starts, 0, $verseCount);

        $resumeAyah = max(1, min($verseCount, (int) ($recitation->manual_sync_ayah ?: 1)));

        return response()->json([
            'data' => [
                'recitation' => (new StaffRecitationResource($recitation))->resolve(),
                'audio_url' => MediaUrl::temporary('r2', $recitation->audio_url),
                'duration' => $recitation->duration,
                'verse_count' => $verseCount,
                'sync_status' => $recitation->sync_status?->value,
                'sync_method' => $recitation->sync_method,
                'sync_error' => $recitation->sync_error,
                'resume_ayah' => $resumeAyah,
                'ayahs' => $ayahs->map(fn (Ayah $a): array => [
                    'number' => (int) $a->number,
                    'text_uthmani' => (string) $a->text_uthmani,
                ])->values()->all(),
                'ayah_starts' => $starts,
            ],
        ]);
    }

    public function save(Request $request, Recitation $recitation): JsonResponse
    {
        $this->authorize('syncAyahs', $recitation);

        $validated = $request->validate([
            'ayah_starts' => ['required', 'array', 'min:1'],
            'ayah_starts.*' => ['numeric', 'min:0'],
            'resume_ayah' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $recitation = $this->sync->saveManualTimings(
                $recitation,
                array_map('floatval', array_values($validated['ayah_starts'])),
                $validated['resume_ayah'] ?? null,
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return $this->show($recitation);
    }

    public function autoSync(Request $request, Recitation $recitation): JsonResponse
    {
        $this->authorize('syncAyahs', $recitation);

        $overwriteManual = $request->boolean('overwrite_manual');

        try {
            $this->sync->sync($recitation, overwriteManual: $overwriteManual);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return $this->show($recitation->fresh());
    }
}
