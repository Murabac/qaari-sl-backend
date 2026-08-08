<?php

namespace App\Services;

use App\Enums\SyncStatus;
use App\Models\Ayah;
use App\Models\Recitation;
use App\Models\RecitationAyahTiming;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AyahTimingSyncService
{
    public function sync(Recitation $recitation, bool $overwriteManual = false): Recitation
    {
        $recitation->refresh();

        if ($recitation->sync_method === 'manual' && ! $overwriteManual) {
            throw new RuntimeException(
                'This recitation has manual timings. Auto-sync will not overwrite them. Use “Replace manual with auto” in admin if you really want to start over.'
            );
        }

        if (blank($recitation->audio_url)) {
            $recitation->update([
                'sync_status' => SyncStatus::MissingAudio,
                'sync_error' => 'No audio file uploaded.',
                'synced_at' => null,
                'sync_method' => null,
                'manual_sync_ayah' => null,
            ]);

            return $recitation->fresh();
        }

        $recitation->update([
            'sync_status' => SyncStatus::Syncing,
            'sync_error' => null,
        ]);

        $tempAudio = null;

        try {
            $verseCount = (int) ($recitation->surah?->verse_count
                ?? Ayah::query()->where('surah_id', $recitation->surah_id)->count());

            if ($verseCount < 1) {
                throw new RuntimeException('Surah verse count is missing.');
            }

            $lengths = Ayah::query()
                ->where('surah_id', $recitation->surah_id)
                ->orderBy('number')
                ->pluck('text_uthmani')
                ->map(fn (string $text): int => mb_strlen(preg_replace('/\s+/u', '', $text) ?? $text))
                ->values()
                ->all();

            if (count($lengths) !== $verseCount) {
                $lengths = array_fill(0, $verseCount, 1);
            }

            $tempAudio = $this->materializeAudio($recitation->audio_url);
            $surahNumber = (int) ($recitation->surah?->number ?? 0);
            $payload = $this->runAligner($tempAudio, $verseCount, $lengths, $surahNumber);

            DB::transaction(function () use ($recitation, $payload, $verseCount): void {
                RecitationAyahTiming::query()
                    ->where('recitation_id', $recitation->id)
                    ->delete();

                $rows = [];
                $now = now();

                foreach ($payload['timings'] as $timing) {
                    $ayah = (int) ($timing['ayah'] ?? 0);
                    if ($ayah < 1 || $ayah > $verseCount) {
                        continue;
                    }

                    $rows[] = [
                        'recitation_id' => $recitation->id,
                        'ayah_number' => $ayah,
                        'start_ms' => max(0, (int) ($timing['start_ms'] ?? 0)),
                        'end_ms' => max(0, (int) ($timing['end_ms'] ?? 0)),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (count($rows) !== $verseCount) {
                    throw new RuntimeException('Aligner returned an incomplete timing set.');
                }

                RecitationAyahTiming::query()->insert($rows);

                $recitation->update([
                    'sync_status' => SyncStatus::Synced,
                    'synced_at' => now(),
                    'sync_error' => null,
                    'sync_method' => (string) ($payload['method'] ?? 'unknown'),
                    'manual_sync_ayah' => null,
                    'duration' => isset($payload['duration_ms'])
                        ? (int) round(((int) $payload['duration_ms']) / 1000)
                        : $recitation->duration,
                ]);
            });

            return $recitation->fresh(['ayahTimings', 'surah']);
        } catch (Throwable $e) {
            // Keep existing manual timings intact if we refused / failed before rewrite.
            if ($recitation->fresh()->sync_method === 'manual' && ! $overwriteManual) {
                throw $e;
            }

            $recitation->update([
                'sync_status' => SyncStatus::Failed,
                'sync_error' => Str::limit($e->getMessage(), 1000),
                'synced_at' => null,
                'sync_method' => null,
                'manual_sync_ayah' => null,
            ]);

            throw $e;
        } finally {
            if ($tempAudio && is_file($tempAudio)) {
                @unlink($tempAudio);
            }
        }
    }

    /**
     * Persist admin-edited ayah start times (seconds). End times are derived from the next start / duration.
     * Safe to call mid-surah — save progress and resume later via $resumeAyah (1-based).
     *
     * @param  list<float|int|string>  $startsSeconds
     */
    public function saveManualTimings(Recitation $recitation, array $startsSeconds, ?int $resumeAyah = null): Recitation
    {
        $recitation->refresh();

        $verseCount = (int) ($recitation->surah?->verse_count
            ?? Ayah::query()->where('surah_id', $recitation->surah_id)->count());

        if ($verseCount < 1) {
            throw new RuntimeException('Surah verse count is missing.');
        }

        if (count($startsSeconds) !== $verseCount) {
            throw new RuntimeException("Expected {$verseCount} start times, got ".count($startsSeconds).'.');
        }

        $durationMs = max(
            0,
            (int) ($recitation->duration ?? 0) * 1000,
            (int) ($recitation->ayahTimings()->max('end_ms') ?? 0),
        );

        if ($durationMs < 1000 && $startsSeconds !== []) {
            // Coolify creates sometimes skip ffprobe; derive a usable length from marks.
            $durationMs = (int) round(max(array_map('floatval', $startsSeconds)) * 1000) + 1000;
        }

        if ($durationMs < 1000) {
            throw new RuntimeException('Recitation duration is missing — open the recitation, re-save the audio, then try again.');
        }

        $normalized = [];
        $prev = -0.05;

        foreach (array_values($startsSeconds) as $i => $raw) {
            $start = max(0.0, (float) $raw);
            if ($i > 0) {
                $start = max($prev + 0.05, $start);
            }
            // Keep room for remaining ayahs (at least 50ms each).
            $maxStart = ($durationMs / 1000) - (0.05 * ($verseCount - $i));
            $start = min($start, max(0.0, $maxStart));
            $normalized[] = $start;
            $prev = $start;
        }

        $resume = $resumeAyah !== null
            ? max(1, min($verseCount, $resumeAyah))
            : null;

        DB::transaction(function () use ($recitation, $normalized, $verseCount, $durationMs, $resume): void {
            RecitationAyahTiming::query()
                ->where('recitation_id', $recitation->id)
                ->delete();

            $rows = [];
            $now = now();

            for ($i = 0; $i < $verseCount; $i++) {
                $startMs = (int) round($normalized[$i] * 1000);
                $endMs = $i === $verseCount - 1
                    ? $durationMs
                    : (int) round($normalized[$i + 1] * 1000);

                if ($endMs <= $startMs) {
                    $endMs = min($durationMs, $startMs + 50);
                }

                $rows[] = [
                    'recitation_id' => $recitation->id,
                    'ayah_number' => $i + 1,
                    'start_ms' => $startMs,
                    'end_ms' => $endMs,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $rows[$verseCount - 1]['end_ms'] = $durationMs;

            RecitationAyahTiming::query()->insert($rows);

            Recitation::withoutEvents(function () use ($recitation, $resume): void {
                $recitation->update([
                    'sync_status' => SyncStatus::Synced,
                    'synced_at' => now(),
                    'sync_error' => null,
                    'sync_method' => 'manual',
                    'manual_sync_ayah' => $resume,
                ]);
            });
        });

        return $recitation->fresh();
    }

    /**
     * @param  list<int>  $lengths
     * @return array{method: string, duration_ms?: int|null, timings: list<array{ayah:int,start_ms:int,end_ms:int}>}
     */
    public function runAligner(string $audioPath, int $verseCount, array $lengths, int $surahNumber = 0): array
    {
        // Prefer the Python script when available; otherwise use the PHP/FFmpeg aligner.
        try {
            if ($this->pythonReady()) {
                return $this->runPythonAligner($audioPath, $verseCount, $lengths, $surahNumber);
            }
        } catch (Throwable) {
            // Fall through to PHP aligner.
        }

        return $this->runPhpAligner($audioPath, $verseCount, $lengths, $surahNumber);
    }

    /**
     * @param  list<int>  $lengths
     * @return array{method: string, duration_ms?: int|null, timings: list<array{ayah:int,start_ms:int,end_ms:int}>}
     */
    public function runPythonAligner(string $audioPath, int $verseCount, array $lengths, int $surahNumber = 0): array
    {
        $ffmpeg = $this->ffmpegPath();
        $ffprobe = $this->ffprobePath();
        $python = $this->resolveBinary((string) config('ayah_sync.python'), 'python');
        $script = (string) config('ayah_sync.script');

        if (! is_file($script)) {
            throw new RuntimeException("Aligner script missing at {$script}");
        }

        $out = storage_path('app/tmp/ayah-sync-'.Str::uuid().'.json');
        if (! is_dir(dirname($out))) {
            mkdir(dirname($out), 0755, true);
        }

        try {
            $result = Process::timeout((int) config('ayah_sync.timeout', 300))
                ->run([
                    $python,
                    $script,
                    '--audio', $audioPath,
                    '--verses', (string) $verseCount,
                    '--lengths', implode(',', $lengths),
                    '--surah', (string) $surahNumber,
                    '--ffmpeg', $ffmpeg,
                    '--ffprobe', $ffprobe,
                    '--out', $out,
                ]);

            if (! $result->successful()) {
                throw new RuntimeException(trim($result->errorOutput() ?: $result->output()) ?: 'Aligner failed.');
            }

            if (! is_file($out)) {
                throw new RuntimeException('Aligner did not write an output file.');
            }

            /** @var array{method?: string, duration_ms?: int, timings?: list<array<string, mixed>>} $payload */
            $payload = json_decode((string) file_get_contents($out), true, 512, JSON_THROW_ON_ERROR);

            if (! isset($payload['timings']) || ! is_array($payload['timings'])) {
                throw new RuntimeException('Aligner output is invalid.');
            }

            return [
                'method' => (string) ($payload['method'] ?? 'python_ffmpeg_silence'),
                'duration_ms' => isset($payload['duration_ms']) ? (int) $payload['duration_ms'] : null,
                'timings' => array_values($payload['timings']),
            ];
        } finally {
            if (is_file($out)) {
                @unlink($out);
            }
        }
    }

    /**
     * @param  list<int>  $lengths
     * @return array{method: string, duration_ms: int, timings: list<array{ayah:int,start_ms:int,end_ms:int}>}
     */
    public function runPhpAligner(string $audioPath, int $verseCount, array $lengths, int $surahNumber = 0): array
    {
        $ffmpeg = $this->ffmpegPath();
        $ffprobe = $this->ffprobePath();

        $durationMs = $this->probeDurationMs($ffprobe, $audioPath);
        $durationS = max(0.001, $durationMs / 1000);

        $wav = storage_path('app/tmp/ayah-align-'.Str::uuid().'.wav');
        if (! is_dir(dirname($wav))) {
            mkdir(dirname($wav), 0755, true);
        }

        try {
            // One downsample pass — silence scans on 8 kHz mono are much faster than on full MP3.
            $convert = Process::timeout((int) config('ayah_sync.timeout', 600))->run([
                $ffmpeg, '-y', '-i', $audioPath,
                '-ac', '1', '-ar', '8000', '-vn', $wav,
            ]);

            if (! $convert->successful() || ! is_file($wav)) {
                throw new RuntimeException('FFmpeg downsample failed: '.trim($convert->errorOutput()));
            }

            // Aggressive profiles catch breath / soft pauses between ayahs (common in murattal).
            $profiles = [
                ['noise' => '-35dB', 'd' => 0.22],
                ['noise' => '-42dB', 'd' => 0.14],
                ['noise' => '-50dB', 'd' => 0.08],
                ['noise' => '-55dB', 'd' => 0.06],
                // Absolute amplitude thresholds help when room tone never reaches true silence.
                ['noise' => '0.025', 'd' => 0.10],
                ['noise' => '0.040', 'd' => 0.08],
            ];

            $merged = [];
            $bestSilences = [];
            $bestScore = PHP_FLOAT_MAX;
            $target = max(1, $verseCount - 1);

            foreach ($profiles as $profile) {
                $detect = Process::timeout((int) config('ayah_sync.timeout', 600))->run([
                    $ffmpeg,
                    '-i', $wav,
                    '-af', sprintf('silencedetect=noise=%s:d=%.2f', $profile['noise'], $profile['d']),
                    '-f', 'null',
                    '-',
                ]);

                $log = $detect->errorOutput()."\n".$detect->output();
                $silences = $this->parseSilences($log);
                $merged = $this->mergeSilences(array_merge($merged, $silences));

                $score = abs(count($silences) - $target);
                if (count($silences) < (int) ($target * 0.35)) {
                    $score += ($target - count($silences)) * 2;
                }

                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestSilences = $silences;
                }
            }

            // Soft energy valleys catch phrase boundaries when FFmpeg finds few true silences.
            $valleys = $this->detectEnergyValleys($wav, $durationS, $verseCount);
            $merged = $this->mergeSilences(array_merge($merged, $valleys));

            // Prefer the union of all profiles when it gives more usable pause candidates.
            $candidates = count($merged) > count($bestSilences) ? $merged : $bestSilences;
            if (count($valleys) > count($candidates)) {
                $candidates = $this->mergeSilences(array_merge($candidates, $valleys));
            }

            $contentStart = $this->detectBasmalaEnd($candidates, $durationS, $surahNumber);

            // If no clear basmala pause but ayah 1 is a short muqatta'at opener,
            // keep a short window for ayah 1 at the end of the opening phrase.
            if ($contentStart < 0.5 && $surahNumber > 1 && $surahNumber !== 9) {
                $firstLen = (int) ($lengths[0] ?? 0);
                if ($firstLen > 0 && $firstLen <= 15) {
                    $openingPause = null;
                    foreach ($candidates as [$start, $end]) {
                        if ($start >= 3.0 && $start <= 20.0) {
                            $openingPause = $start;
                            break;
                        }
                    }
                    if ($openingPause !== null) {
                        $ayah1Dur = max(0.7, min(2.8, $firstLen * 0.35));
                        $contentStart = max(0.0, $openingPause - $ayah1Dur);
                    }
                }
            }

            $contentDuration = max(0.5, $durationS - $contentStart);

            $relativeSilences = [];
            foreach ($candidates as [$start, $end]) {
                if ($end <= $contentStart + 0.05) {
                    continue;
                }
                $relativeSilences[] = [
                    max(0.0, $start - $contentStart),
                    max(0.0, $end - $contentStart),
                ];
            }

            $mappedRelative = $this->mapWithSilenceSnap(
                $relativeSilences,
                $verseCount,
                $lengths,
                $contentDuration,
            );

            if (count($mappedRelative) !== $verseCount) {
                return $this->equalSplitPayload($verseCount, $durationMs, 'equal_split');
            }

            $mapped = array_map(
                fn (array $pair): array => [$pair[0] + $contentStart, $pair[1] + $contentStart],
                $mappedRelative,
            );
            $mapped[0][0] = $contentStart;
            $mapped[count($mapped) - 1][1] = $durationS;

            $usablePauses = count(array_filter(
                $relativeSilences,
                fn (array $s): bool => ($s[1] - $s[0]) >= 0.05,
            ));

            $method = $usablePauses >= (int) (($verseCount - 1) * 0.35)
                ? ($contentStart > 0.5 ? 'ffmpeg_silence_snap_basmala' : 'ffmpeg_silence_snap')
                : ($contentStart > 0.5 ? 'ffmpeg_silence_weighted_basmala' : 'ffmpeg_silence_weighted');

            return [
                'method' => $method,
                'duration_ms' => $durationMs,
                'timings' => collect($mapped)->values()->map(fn (array $pair, int $i): array => [
                    'ayah' => $i + 1,
                    'start_ms' => (int) round($pair[0] * 1000),
                    'end_ms' => (int) round($pair[1] * 1000),
                ])->all(),
            ];
        } finally {
            if (is_file($wav)) {
                @unlink($wav);
            }
        }
    }

    public function materializeAudio(string $path): string
    {
        $dir = storage_path('app/tmp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'mp3';
        $local = $dir.'/ayah-audio-'.Str::uuid().'.'.$ext;

        if (Storage::disk('r2')->exists($path)) {
            $stream = Storage::disk('r2')->readStream($path);
            if ($stream === false) {
                throw new RuntimeException('Unable to read audio from storage.');
            }
            $out = fopen($local, 'wb');
            if ($out === false) {
                fclose($stream);
                throw new RuntimeException('Unable to create temp audio file.');
            }
            stream_copy_to_stream($stream, $out);
            fclose($stream);
            fclose($out);

            return $local;
        }

        if (is_file($path)) {
            copy($path, $local);

            return $local;
        }

        throw new RuntimeException('Audio file not found in storage.');
    }

    public function toolchainReady(): array
    {
        $ffmpeg = $this->ffmpegPath();
        $ffprobe = $this->ffprobePath();
        $python = $this->resolveBinary((string) config('ayah_sync.python'), 'python');
        $script = (string) config('ayah_sync.script');

        $ffmpegOk = $this->binaryWorks($ffmpeg, ['-version']);
        $ffprobeOk = $this->binaryWorks($ffprobe, ['-version']);
        $pythonOk = $this->pythonReady();

        return [
            'ffmpeg' => $ffmpeg,
            'ffprobe' => $ffprobe,
            'python' => $python,
            'script' => $script,
            'ffmpeg_ok' => $ffmpegOk,
            'ffprobe_ok' => $ffprobeOk,
            'python_ok' => $pythonOk,
            'script_ok' => is_file($script),
            // Ready when FFmpeg works — Python is optional enhancement.
            'ready' => $ffmpegOk && $ffprobeOk,
        ];
    }

    public function ffmpegPath(): string
    {
        $configured = (string) config('ayah_sync.ffmpeg');
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $npm = base_path('node_modules/ffmpeg-static/ffmpeg.exe');
        if (is_file($npm)) {
            return $npm;
        }

        $npmUnix = base_path('node_modules/ffmpeg-static/ffmpeg');
        if (is_file($npmUnix)) {
            return $npmUnix;
        }

        return $this->resolveBinary($configured !== '' ? $configured : base_path('tools/bin/ffmpeg'), 'ffmpeg');
    }

    public function ffprobePath(): string
    {
        $configured = (string) config('ayah_sync.ffprobe');
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $npm = base_path('node_modules/ffprobe-static/bin/win32/x64/ffprobe.exe');
        if (is_file($npm)) {
            return $npm;
        }

        foreach ([
            base_path('node_modules/ffprobe-static/ffprobe'),
            base_path('node_modules/@ffprobe-installer/ffprobe/ffprobe.exe'),
        ] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        // ffprobe-static package structure varies — search
        $matches = glob(base_path('node_modules/ffprobe-static/**/ffprobe.exe'));
        if (! empty($matches[0])) {
            return $matches[0];
        }
        $matches = glob(base_path('node_modules/ffprobe-static/**/ffprobe'));
        if (! empty($matches[0])) {
            return $matches[0];
        }

        return $this->resolveBinary($configured !== '' ? $configured : base_path('tools/bin/ffprobe'), 'ffprobe');
    }

    public function resolveBinary(string $configured, string $fallbackName): string
    {
        $candidates = array_filter([
            $configured,
            base_path('tools/bin/'.$fallbackName.($this->isWindows() ? '.exe' : '')),
            base_path('tools/bin/'.$fallbackName),
            $fallbackName,
        ]);

        foreach ($candidates as $candidate) {
            if ($candidate === $fallbackName) {
                return $fallbackName;
            }

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $fallbackName;
    }

    private function pythonReady(): bool
    {
        $python = $this->resolveBinary((string) config('ayah_sync.python'), 'python');

        return $this->binaryWorks($python, ['--version']) && is_file((string) config('ayah_sync.script'));
    }

    /**
     * @param  list<string>  $args
     */
    private function binaryWorks(string $binary, array $args): bool
    {
        try {
            $result = Process::timeout(15)->run(array_merge([$binary], $args));

            return $result->successful() || str_contains($result->output().$result->errorOutput(), 'version') || str_contains($result->output().$result->errorOutput(), 'ffmpeg');
        } catch (Throwable) {
            return false;
        }
    }

    private function probeDurationMs(string $ffprobe, string $audioPath): int
    {
        $result = Process::timeout(60)->run([
            $ffprobe, '-v', 'error', '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1', $audioPath,
        ]);

        if (! $result->successful()) {
            throw new RuntimeException('ffprobe failed: '.trim($result->errorOutput()));
        }

        return max(0, (int) round(((float) trim($result->output())) * 1000));
    }

    /**
     * @return list<array{0: float, 1: float}>
     */
    private function parseSilences(string $log): array
    {
        preg_match_all('/silence_start:\s*([0-9.]+)/', $log, $starts);
        preg_match_all('/silence_end:\s*([0-9.]+)/', $log, $ends);

        $startVals = array_map('floatval', $starts[1] ?? []);
        $endVals = array_map('floatval', $ends[1] ?? []);
        $pairs = [];
        $i = $j = 0;

        while ($i < count($startVals) && $j < count($endVals)) {
            if ($endVals[$j] < $startVals[$i]) {
                $j++;

                continue;
            }
            $pairs[] = [$startVals[$i], $endVals[$j]];
            $i++;
            $j++;
        }

        return $pairs;
    }

    /**
     * Merge overlapping / near-adjacent silence intervals.
     *
     * @param  list<array{0: float, 1: float}>  $silences
     * @return list<array{0: float, 1: float}>
     */
    private function mergeSilences(array $silences): array
    {
        if ($silences === []) {
            return [];
        }

        usort($silences, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $out = [$silences[0]];
        for ($i = 1; $i < count($silences); $i++) {
            $last = count($out) - 1;
            [$a0, $a1] = $out[$last];
            [$b0, $b1] = $silences[$i];

            if ($b0 <= $a1 + 0.12) {
                $out[$last] = [$a0, max($a1, $b1)];
            } else {
                $out[] = [$b0, $b1];
            }
        }

        return $out;
    }

    /**
     * For surahs other than Al-Fatiha / At-Tawbah, audio usually starts with basmala
     * which is not numbered as ayah 1 — shift content start past that opening pause.
     *
     * @param  list<array{0: float, 1: float}>  $silences
     */
    private function detectBasmalaEnd(array $silences, float $durationS, int $surahNumber): float
    {
        if ($surahNumber < 1 || in_array($surahNumber, [1, 9], true)) {
            return 0.0;
        }

        $bestEnd = 0.0;
        $bestLen = 0.0;

        foreach ($silences as [$start, $end]) {
            if ($start < 1.8 || $start > 14.0 || $end >= $durationS - 1.0) {
                continue;
            }

            $len = $end - $start;
            if ($len >= 0.18 && $len > $bestLen) {
                $bestLen = $len;
                $bestEnd = $end;
            }
        }

        return $bestEnd;
    }

    /**
     * Find soft energy valleys (local RMS minima) as ayah-boundary candidates.
     * Works when murattal has breath dips but no true digital silence.
     *
     * @return list<array{0: float, 1: float}>
     */
    private function detectEnergyValleys(string $wavPath, float $durationS, int $verseCount): array
    {
        $handle = @fopen($wavPath, 'rb');
        if ($handle === false) {
            return [];
        }

        try {
            $header = fread($handle, 12);
            if ($header === false || strlen($header) < 12 || substr($header, 0, 4) !== 'RIFF') {
                return [];
            }

            $sampleRate = 8000;
            $bitsPerSample = 16;
            $channels = 1;
            $dataSize = null;

            while (! feof($handle)) {
                $chunkHeader = fread($handle, 8);
                if ($chunkHeader === false || strlen($chunkHeader) < 8) {
                    break;
                }

                $chunkId = substr($chunkHeader, 0, 4);
                $chunkSize = unpack('V', substr($chunkHeader, 4, 4))[1] ?? 0;

                if ($chunkId === 'fmt ') {
                    $fmt = fread($handle, $chunkSize);
                    if ($fmt === false || strlen($fmt) < 16) {
                        return [];
                    }
                    $channels = unpack('v', substr($fmt, 2, 2))[1] ?? 1;
                    $sampleRate = unpack('V', substr($fmt, 4, 4))[1] ?? 8000;
                    $bitsPerSample = unpack('v', substr($fmt, 14, 2))[1] ?? 16;

                    continue;
                }

                if ($chunkId === 'data') {
                    $dataSize = $chunkSize;
                    break;
                }

                if ($chunkSize > 0) {
                    fseek($handle, $chunkSize, SEEK_CUR);
                }
            }

            if ($dataSize === null || $channels < 1 || $sampleRate < 1000) {
                return [];
            }

            $bytesPerSample = (int) max(1, $bitsPerSample / 8);
            $frameMs = 0.05;
            $frameSamples = max(1, (int) round($sampleRate * $frameMs));
            $frameBytes = $frameSamples * $channels * $bytesPerSample;
            $rms = [];

            $remaining = $dataSize;
            while ($remaining >= $frameBytes) {
                $raw = fread($handle, $frameBytes);
                if ($raw === false || strlen($raw) < $frameBytes) {
                    break;
                }
                $remaining -= $frameBytes;

                $sum = 0.0;
                $count = 0;
                if ($bitsPerSample === 16) {
                    $samples = unpack('s*', $raw);
                    foreach ($samples as $sample) {
                        $sum += ($sample / 32768.0) ** 2;
                        $count++;
                    }
                } elseif ($bitsPerSample === 8) {
                    for ($i = 0; $i < strlen($raw); $i++) {
                        $v = (ord($raw[$i]) - 128) / 128.0;
                        $sum += $v * $v;
                        $count++;
                    }
                } else {
                    break;
                }

                $rms[] = $count > 0 ? sqrt($sum / $count) : 0.0;
            }

            $n = count($rms);
            if ($n < 20) {
                return [];
            }

            // Light smoothing
            $smooth = $rms;
            for ($i = 1; $i < $n - 1; $i++) {
                $smooth[$i] = ($rms[$i - 1] + $rms[$i] * 2.0 + $rms[$i + 1]) / 4.0;
            }

            $sorted = $smooth;
            sort($sorted);
            $floor = $sorted[(int) max(0, floor(($n - 1) * 0.22))] ?? 0.0;
            $speech = $sorted[(int) min($n - 1, floor(($n - 1) * 0.60))] ?? 0.0;
            $threshold = max($floor * 1.15, $speech * 0.45);

            $minGapFrames = max(3, (int) round(0.35 / $frameMs));
            $target = max(1, $verseCount - 1);
            $rawValleys = [];

            for ($i = 2; $i < $n - 2; $i++) {
                $v = $smooth[$i];
                if ($v > $threshold) {
                    continue;
                }
                if ($v <= $smooth[$i - 1] && $v <= $smooth[$i + 1] && $v <= $smooth[$i - 2] && $v <= $smooth[$i + 2]) {
                    $t = $i * $frameMs;
                    if ($t > 0.4 && $t < $durationS - 0.4) {
                        $rawValleys[] = [$t, $v];
                    }
                }
            }

            if ($rawValleys === []) {
                return [];
            }

            // Keep deepest valleys, spaced apart — prefer about 1.5x verse boundaries.
            usort($rawValleys, fn (array $a, array $b): int => $a[1] <=> $b[1]);
            $keep = (int) min(count($rawValleys), max($target * 2, $target + 10));
            $picked = array_slice($rawValleys, 0, $keep);
            usort($picked, fn (array $a, array $b): int => $a[0] <=> $b[0]);

            $filtered = [];
            foreach ($picked as [$t, $v]) {
                if ($filtered !== []) {
                    $prev = $filtered[count($filtered) - 1][0];
                    if (($t - $prev) < ($minGapFrames * $frameMs)) {
                        // keep deeper of the two
                        if ($v < $filtered[count($filtered) - 1][1]) {
                            $filtered[count($filtered) - 1] = [$t, $v];
                        }

                        continue;
                    }
                }
                $filtered[] = [$t, $v];
            }

            $out = [];
            foreach ($filtered as [$t]) {
                $out[] = [max(0.0, $t - 0.06), min($durationS, $t + 0.06)];
            }

            return $out;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<array{0: float, 1: float}>  $silences
     * @return list<array{0: float, 1: float}>
     */
    private function speechSegments(float $durationS, array $silences): array
    {
        $segments = [];
        $cursor = 0.0;

        foreach ($silences as [$start, $end]) {
            if ($start > $cursor + 0.05) {
                $segments[] = [$cursor, $start];
            }
            $cursor = max($cursor, $end);
        }

        if ($durationS > $cursor + 0.05) {
            $segments[] = [$cursor, $durationS];
        }

        if ($segments === []) {
            $segments = [[0.0, $durationS]];
        }

        $cleaned = array_values(array_filter($segments, fn (array $s): bool => ($s[1] - $s[0]) >= 0.12));

        return $cleaned !== [] ? $cleaned : [[0.0, $durationS]];
    }

    /**
     * Snap ayah boundaries to silence midpoints near text-length-weighted targets.
     *
     * @param  list<array{0: float, 1: float}>  $silences
     * @param  list<int>  $lengths
     * @return list<array{0: float, 1: float}>
     */
    private function mapWithSilenceSnap(array $silences, int $verseCount, array $lengths, float $durationS): array
    {
        if ($verseCount <= 0) {
            return [];
        }

        $weights = $this->weightsFromLengths($lengths, $verseCount);
        $candidates = [0.0];

        foreach ($silences as [$start, $end]) {
            $mid = ($start + $end) / 2.0;
            if ($mid > 0.15 && $mid < $durationS - 0.15) {
                $candidates[] = $mid;
            }
        }

        $candidates[] = $durationS;
        $candidates = array_values(array_unique(array_map(
            fn (float $v): float => round($v, 3),
            $candidates,
        )));
        sort($candidates);

        $ideals = [];
        $acc = 0.0;
        for ($i = 0; $i < $verseCount - 1; $i++) {
            $acc += $weights[$i];
            $ideals[] = $acc * $durationS;
        }

        $snapWindow = max(2.0, min(12.0, $durationS * 0.012));
        $chosen = [0.0];
        $used = [
            '0' => true,
            (string) round($durationS, 3) => true,
        ];

        foreach ($ideals as $idx => $ideal) {
            $prev = $chosen[count($chosen) - 1];
            $remainingAyahs = $verseCount - count($chosen); // includes current boundary's following ayahs after this cut
            $minGap = max(0.7, ($weights[$idx] ?? (1 / $verseCount)) * $durationS * 0.22);
            // Leave room for remaining ayahs after this boundary.
            $maxAllowed = $durationS - max(0.5, 0.45 * $remainingAyahs);
            $best = null;
            $bestDist = PHP_FLOAT_MAX;

            foreach ($candidates as $candidate) {
                $key = (string) round($candidate, 3);
                if (isset($used[$key])) {
                    continue;
                }
                if ($candidate <= $prev + $minGap) {
                    continue;
                }
                if ($candidate > $maxAllowed) {
                    continue;
                }

                $dist = abs($candidate - $ideal);
                if ($dist < $bestDist) {
                    $bestDist = $dist;
                    $best = $candidate;
                }
            }

            if ($best !== null && $bestDist <= $snapWindow) {
                $chosen[] = $best;
                $used[(string) round($best, 3)] = true;
            } else {
                $chosen[] = min($maxAllowed, max($prev + $minGap, $ideal));
            }
        }

        $chosen[] = $durationS;

        $out = [];
        for ($i = 0; $i < $verseCount; $i++) {
            $start = $chosen[$i];
            $end = $chosen[$i + 1];
            if ($end <= $start) {
                $end = min($durationS, $start + 0.25);
            }
            $out[] = [$start, $end];
        }

        if ($out !== []) {
            $out[count($out) - 1][1] = $durationS;
        }

        return $this->repairShortSegments($out, $weights, $durationS);
    }

    /**
     * Absorb tiny accidental segments into neighbors so no ayah is absurdly short.
     *
     * @param  list<array{0: float, 1: float}>  $segments
     * @param  list<float>  $weights
     * @return list<array{0: float, 1: float}>
     */
    private function repairShortSegments(array $segments, array $weights, float $durationS): array
    {
        $n = count($segments);
        if ($n < 2) {
            return $segments;
        }

        for ($pass = 0; $pass < 3; $pass++) {
            $changed = false;

            for ($i = 0; $i < $n; $i++) {
                $len = $segments[$i][1] - $segments[$i][0];
                $minLen = max(0.8, ($weights[$i] ?? (1 / $n)) * $durationS * 0.25);

                if ($len >= $minLen) {
                    continue;
                }

                if ($i === 0) {
                    $segments[1][0] = $segments[0][0];
                    $mid = $segments[0][0] + max($minLen, ($segments[1][1] - $segments[0][0]) * ($weights[0] / max(0.001, $weights[0] + $weights[1])));
                    $mid = min($segments[1][1] - 0.5, max($segments[0][0] + 0.5, $mid));
                    $segments[0][1] = $mid;
                    $segments[1][0] = $mid;
                } elseif ($i === $n - 1) {
                    $prev = $i - 1;
                    $mid = $segments[$prev][1] - max($minLen, ($segments[$i][1] - $segments[$prev][0]) * ($weights[$i] / max(0.001, $weights[$prev] + $weights[$i])));
                    $mid = max($segments[$prev][0] + 0.5, min($segments[$i][1] - 0.5, $mid));
                    $segments[$prev][1] = $mid;
                    $segments[$i][0] = $mid;
                } else {
                    // Steal time from the longer neighbor.
                    $leftLen = $segments[$i - 1][1] - $segments[$i - 1][0];
                    $rightLen = $segments[$i + 1][1] - $segments[$i + 1][0];
                    $need = $minLen - $len;

                    if ($leftLen >= $rightLen) {
                        $segments[$i][0] -= $need;
                        $segments[$i - 1][1] = $segments[$i][0];
                    } else {
                        $segments[$i][1] += $need;
                        $segments[$i + 1][0] = $segments[$i][1];
                    }
                }

                $changed = true;
            }

            if (! $changed) {
                break;
            }
        }

        $segments[0][0] = max(0.0, $segments[0][0]);
        $segments[$n - 1][1] = $durationS;

        return $segments;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $segments
     * @param  list<int>  $lengths
     * @return list<array{0: float, 1: float}>
     *
     * @deprecated Kept for reference; mapping now uses mapWithSilenceSnap().
     */
    private function mapSegmentsToAyahs(array $segments, int $verseCount, array $lengths, float $durationS): array
    {
        return $this->mapWithSilenceSnap(
            $this->segmentsToGapSilences($segments),
            $verseCount,
            $lengths,
            $durationS,
        );
    }

    /**
     * @param  list<array{0: float, 1: float}>  $segments
     * @return list<array{0: float, 1: float}>
     */
    private function segmentsToGapSilences(array $segments): array
    {
        $gaps = [];
        for ($i = 0; $i < count($segments) - 1; $i++) {
            $gaps[] = [$segments[$i][1], $segments[$i + 1][0]];
        }

        return $gaps;
    }

    /**
     * @param  list<int>  $lengths
     * @return list<float>
     */
    private function weightsFromLengths(array $lengths, int $verseCount): array
    {
        if (count($lengths) !== $verseCount) {
            return array_fill(0, $verseCount, 1 / $verseCount);
        }

        $total = array_sum(array_map(fn (int $n): int => max(1, $n), $lengths)) ?: $verseCount;

        return array_map(fn (int $n): float => max(1, $n) / $total, $lengths);
    }

    /**
     * @return array{method: string, duration_ms: int, timings: list<array{ayah:int,start_ms:int,end_ms:int}>}
     */
    private function equalSplitPayload(int $verseCount, int $durationMs, string $method): array
    {
        $durationS = max(0.001, $durationMs / 1000);
        $step = $durationS / max(1, $verseCount);
        $timings = [];

        for ($i = 0; $i < $verseCount; $i++) {
            $start = $i * $step;
            $end = $i === $verseCount - 1 ? $durationS : ($i + 1) * $step;
            $timings[] = [
                'ayah' => $i + 1,
                'start_ms' => (int) round($start * 1000),
                'end_ms' => (int) round($end * 1000),
            ];
        }

        return [
            'method' => $method,
            'duration_ms' => $durationMs,
            'timings' => $timings,
        ];
    }

    private function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}
