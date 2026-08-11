<?php

namespace App\Livewire;

use App\Models\Ayah;
use App\Models\Recitation;
use App\Services\AyahTimingSyncService;
use App\Support\MediaUrl;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Throwable;

class RecitationAyahSyncPanel extends Component
{
    #[Locked]
    public int $recitationId;

    public function mount(Recitation $record): void
    {
        $this->recitationId = $record->id;
    }

    #[Renderless]
    public function saveManualTimings(mixed $startsSeconds = [], mixed $resumeAyah = null): void
    {
        try {
            $starts = collect(is_array($startsSeconds) ? $startsSeconds : [])
                ->map(fn ($value): float => round((float) $value, 3))
                ->values()
                ->all();

            $resume = ($resumeAyah === null || $resumeAyah === '')
                ? null
                : (int) $resumeAyah;

            $recitation = Recitation::query()->findOrFail($this->recitationId);

            app(AyahTimingSyncService::class)->saveManualTimings(
                $recitation,
                $starts,
                $resume,
            );

            Notification::make()
                ->title('Progress saved')
                ->body(
                    $resume
                        ? "All set. Next time we’ll open ayah {$resume} for you."
                        : 'All set. You can leave and continue later anytime.'
                )
                ->success()
                ->send();
        } catch (Throwable $e) {
            Log::error('RecitationAyahSyncPanel::saveManualTimings failed', [
                'recitation_id' => $this->recitationId,
                'error' => $e->getMessage(),
            ]);
            report($e);

            Notification::make()
                ->title('Could not save timings')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        $record = Recitation::query()->find($this->recitationId);

        if (! $record) {
            return view('livewire.recitation-ayah-sync-missing');
        }

        $ayahRows = Ayah::query()
            ->where('surah_id', $record->surah_id)
            ->orderBy('number')
            ->get(['number', 'text_uthmani'])
            ->map(fn (Ayah $a): array => [
                'n' => (int) $a->number,
                't' => (string) $a->text_uthmani,
            ])
            ->values()
            ->all();

        $timingRows = $record->ayahTimings()
            ->orderBy('ayah_number')
            ->get(['ayah_number', 'start_ms', 'end_ms'])
            ->map(fn ($t): array => [
                'ayah_number' => (int) $t->ayah_number,
                'start_ms' => (int) $t->start_ms,
                'end_ms' => (int) $t->end_ms,
            ])
            ->values()
            ->all();

        return view('filament.forms.ayah-sync-panel', [
            'record' => $record,
            'ayahRows' => $ayahRows,
            'timingRows' => $timingRows,
            'audioUrl' => filled($record->audio_url)
                ? MediaUrl::temporary('r2', $record->audio_url)
                : null,
            'verseCount' => (int) ($record->surah()->value('verse_count') ?: count($ayahRows) ?: 1),
        ]);
    }
}
