<?php

namespace App\Console\Commands;

use App\Models\Recitation;
use App\Services\AyahTimingSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncAyahTimingsCommand extends Command
{
    protected $signature = 'ayah:sync
        {recitation? : Recitation ID to sync}
        {--all : Sync every recitation that has audio}
        {--pending : Sync only pending/failed recitations}
        {--force : Overwrite recitations that already have manual timings}
        {--check : Show toolchain status and exit}';

    protected $description = 'Generate ayah timings for follow-along sync using FFmpeg + Python';

    public function handle(AyahTimingSyncService $sync): int
    {
        $toolchain = $sync->toolchainReady();

        if ($this->option('check') || (! $this->argument('recitation') && ! $this->option('all') && ! $this->option('pending'))) {
            $this->table(
                ['Tool', 'Path', 'OK'],
                [
                    ['ffmpeg', $toolchain['ffmpeg'], $toolchain['ffmpeg_ok'] ? 'yes' : 'no'],
                    ['ffprobe', $toolchain['ffprobe'], $toolchain['ffprobe_ok'] ? 'yes' : 'no'],
                    ['python', $toolchain['python'], $toolchain['python_ok'] ? 'yes' : 'no'],
                    ['script', $toolchain['script'], $toolchain['script_ok'] ? 'yes' : 'no'],
                ],
            );

            if (! $toolchain['ready']) {
                $this->warn('Tools incomplete. Run: powershell -File tools/install-sync-tools.ps1');
            } else {
                $this->info('Sync toolchain ready.');
            }

            if ($this->option('check')) {
                return self::SUCCESS;
            }

            if (! $this->argument('recitation') && ! $this->option('all') && ! $this->option('pending')) {
                $this->comment('Pass a recitation id, --all, or --pending to run sync.');

                return self::SUCCESS;
            }
        }

        if (! $toolchain['ready']) {
            $this->error('Sync tools are not ready. Run: powershell -File tools/install-sync-tools.ps1');

            return self::FAILURE;
        }

        $query = Recitation::query()->with('surah')->whereNotNull('audio_url');

        if ($id = $this->argument('recitation')) {
            $query->whereKey($id);
        } elseif ($this->option('pending')) {
            $query->whereIn('sync_status', ['pending', 'failed', 'syncing']);
        }

        $recitations = $query->orderBy('id')->get();

        if ($recitations->isEmpty()) {
            $this->warn('No matching recitations.');

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;
        $skipped = 0;
        $force = (bool) $this->option('force');

        foreach ($recitations as $recitation) {
            $label = "#{$recitation->id} surah ".$recitation->surah?->number;

            if ($recitation->sync_method === 'manual' && ! $force) {
                $skipped++;
                $this->line("  → skipped {$label} (manual timings; use --force to overwrite)");

                continue;
            }

            try {
                $this->info("Syncing {$label}…");
                $sync->sync($recitation, overwriteManual: $force);
                $ok++;
                $this->line('  → synced ('.$recitation->fresh()->sync_method.')');
            } catch (Throwable $e) {
                $fail++;
                $this->error("  → failed: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done. Synced {$ok}, skipped {$skipped}, failed {$fail}.");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
