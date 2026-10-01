@php
    use App\Enums\SyncStatus;
    /** @var \App\Models\Recitation|null $record */
    /** @var list<array{n:int,t:string}> $ayahRows */
    /** @var list<array{ayah_number:int,start_ms:int,end_ms:int}> $timingRows */
    $ayahRows = $ayahRows ?? [];
    $timingRows = $timingRows ?? [];
    $audioUrl = $audioUrl ?? null;
    $reciterName = $reciterName ?? null;
    $surahLabel = $surahLabel ?? null;
    $status = $record?->sync_status ?? SyncStatus::Pending;
    $durationSeconds = max(1, (int) ($record?->duration ?? 0));
    $verseCount = max(1, (int) ($verseCount ?? 0) ?: count($ayahRows) ?: 1);

    if ($timingRows !== []) {
        $starts = collect($timingRows)
            ->sortBy('ayah_number')
            ->map(fn (array $t) => round($t['start_ms'] / 1000, 3))
            ->values()
            ->all();
    } else {
        $step = $durationSeconds / $verseCount;
        $starts = [];
        for ($i = 0; $i < $verseCount; $i++) {
            $starts[] = round($i * $step, 3);
        }
    }

    if (count($starts) < $verseCount) {
        $step = $durationSeconds / $verseCount;
        while (count($starts) < $verseCount) {
            $starts[] = round(count($starts) * $step, 3);
        }
    }
    $starts = array_slice($starts, 0, $verseCount);

    $ayahPayload = $ayahRows !== []
        ? $ayahRows
        : collect(range(1, $verseCount))->map(fn (int $n) => ['n' => $n, 't' => ''])->all();

    $isSynced = $status === SyncStatus::Synced;
    $isFailed = $status === SyncStatus::Failed;
    $resumeAyah = max(1, min($verseCount, (int) ($record?->manual_sync_ayah ?: 1)));
    $isManual = $record?->sync_method === 'manual';
    $metaLine = collect([$reciterName, $surahLabel])->filter()->implode(' · ');
    $syncProgress = $syncProgress ?? null;
    $isSyncRunning = (bool) ($isSyncRunning ?? false);
    $progressPercent = (int) ($syncProgress['percent'] ?? ($isSyncRunning ? 8 : 0));
    $progressLabel = (string) ($syncProgress['label'] ?? ($isSyncRunning
        ? 'Automatic matching is running in the background…'
        : ''));
    $showProgress = $isSyncRunning
        || in_array($syncProgress['status'] ?? '', ['pending', 'syncing', 'failed'], true)
        || ($isFailed && filled($progressLabel));
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
    .qaari-sync { font-family: ui-sans-serif, system-ui, sans-serif; color: #1f2937; }
    .qaari-sync * { box-sizing: border-box; }
    .qaari-sync-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        overflow: hidden;
    }
    .qaari-sync-head {
        padding: 16px 20px;
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: flex-start;
        justify-content: space-between;
    }
    .qaari-sync-title { margin: 0; font-size: 1.05rem; font-weight: 800; }
    .qaari-sync-meta { margin: 4px 0 0; font-size: 0.9rem; font-weight: 700; color: #374151; }
    .qaari-sync-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
    .qaari-sync-badge {
        display: inline-flex; align-items: center; border-radius: 999px;
        padding: 4px 12px; font-size: 0.75rem; font-weight: 700;
        background: #f3f4f6; color: #374151;
    }
    .qaari-sync-badge.is-ok { background: #d1fae5; color: #065f46; }
    .qaari-sync-badge.is-bad { background: #fee2e2; color: #991b1b; }
    .qaari-sync-badge.is-warn { background: #fef3c7; color: #92400e; }
    .qaari-sync-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .qaari-sync-body { padding: 20px; display: grid; gap: 16px; }
    .qaari-sync-focus {
        border: 2px solid #f59e0b;
        border-radius: 16px;
        background: #fffbeb;
        padding: 20px;
    }
    .qaari-sync-nav {
        display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px;
    }
    .qaari-sync-step { text-align: center; flex: 1; }
    .qaari-sync-step strong { display: block; font-size: 0.95rem; color: #92400e; }
    .qaari-sync-step span { font-size: 0.8rem; color: #78716c; }
    .qaari-sync-ayah {
        text-align: center;
        direction: rtl;
        font-family: "Amiri", "Scheherazade New", "Noto Naskh Arabic", serif;
        font-size: clamp(1.45rem, 2.6vw, 1.95rem);
        line-height: 2.15;
        color: #111827;
        min-height: 5.5rem;
        padding: 8px 4px;
    }
    .qaari-sync-btn {
        appearance: none; border: 1px solid #d1d5db; background: #fff; color: #111827;
        border-radius: 12px; padding: 10px 14px; font-size: 0.875rem; font-weight: 700;
        cursor: pointer; line-height: 1.2;
    }
    .qaari-sync-btn:hover { background: #f9fafb; }
    .qaari-sync-btn:disabled { opacity: 0.45; cursor: not-allowed; }
    .qaari-sync-btn-primary { background: #0C403E; border-color: #0C403E; color: #fff; }
    .qaari-sync-btn-primary:hover { background: #1C5A58; }
    .qaari-sync-btn-mark {
        background: #C9A24B; border-color: #C9A24B; color: #0C403E;
        width: 100%; padding: 16px 18px; font-size: 1.05rem; border-radius: 14px;
    }
    .qaari-sync-btn-mark:hover { background: #d4b05e; }
    .qaari-sync-btn-save {
        background: #059669; border-color: #059669; color: #fff;
        width: 100%; padding: 14px 18px; font-size: 1rem; border-radius: 14px;
    }
    .qaari-sync-btn-save:hover { background: #10b981; }
    .qaari-sync-btn-save:disabled { background: #a7f3d0; border-color: #a7f3d0; color: #065f46; }
    .qaari-sync-transport {
        display: grid; gap: 12px; padding: 16px; border: 1px solid #e5e7eb;
        border-radius: 14px; background: #fff;
    }
    .qaari-sync-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .qaari-sync-clock { font-variant-numeric: tabular-nums; font-weight: 700; font-size: 0.9rem; color: #4b5563; }
    .qaari-sync-range { width: 100%; accent-color: #0C403E; height: 28px; }
    .qaari-sync-hint {
        margin: 0; font-size: 0.85rem; color: #6b7280; line-height: 1.45;
    }
    .qaari-sync-check {
        display: flex; gap: 10px; align-items: center;
        font-size: 0.9rem; font-weight: 600; color: #374151;
    }
    .qaari-sync-list-title { margin: 0; font-size: 1rem; font-weight: 800; }
    .qaari-sync-list {
        max-height: 360px; overflow: auto; border: 1px solid #e5e7eb;
        border-radius: 12px; background: #fff;
    }
    .qaari-sync-item {
        display: grid; grid-template-columns: 40px 1fr 88px; gap: 10px;
        width: 100%; text-align: left; border: 0; border-bottom: 1px solid #f3f4f6;
        background: #fff; padding: 12px 14px; cursor: pointer; align-items: center;
    }
    .qaari-sync-item:last-child { border-bottom: 0; }
    .qaari-sync-item:hover { background: #f9fafb; }
    .qaari-sync-item.is-active { background: rgba(201, 162, 75, 0.15); }
    .qaari-sync-item-num {
        width: 32px; height: 32px; border-radius: 999px; display: inline-flex;
        align-items: center; justify-content: center; font-weight: 800; font-size: 0.75rem;
        background: #e5e7eb; color: #111827;
    }
    .qaari-sync-item.is-active .qaari-sync-item-num { background: #0C403E; color: #f7f4ee; }
    .qaari-sync-item-text {
        direction: rtl; text-align: right; font-family: "Amiri", "Noto Naskh Arabic", serif;
        font-size: 1.1rem; line-height: 1.8; color: #111827;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .qaari-sync-item-time {
        font-variant-numeric: tabular-nums; font-size: 0.75rem; color: #6b7280;
        text-align: right;
    }
    .qaari-sync-error {
        background: #fef2f2; color: #991b1b; border-radius: 12px; padding: 12px 14px; font-size: 0.875rem;
    }
    .qaari-sync-progress {
        border: 1px solid #c9a24b55;
        border-radius: 14px;
        background: linear-gradient(180deg, #fffbeb 0%, #fff 100%);
        padding: 14px 16px;
        display: grid;
        gap: 10px;
    }
    .qaari-sync-progress.is-fail {
        border-color: #fecaca;
        background: linear-gradient(180deg, #fef2f2 0%, #fff 100%);
    }
    .qaari-sync-progress-top {
        display: flex; flex-wrap: wrap; gap: 8px;
        align-items: center; justify-content: space-between;
    }
    .qaari-sync-progress-label {
        margin: 0; font-size: 0.9rem; font-weight: 700; color: #92400e; line-height: 1.4;
    }
    .qaari-sync-progress.is-fail .qaari-sync-progress-label { color: #991b1b; }
    .qaari-sync-progress-pct {
        font-variant-numeric: tabular-nums; font-size: 0.8rem; font-weight: 800; color: #0C403E;
    }
    .qaari-sync-bar {
        height: 10px; border-radius: 999px; background: #e5e7eb; overflow: hidden;
    }
    .qaari-sync-bar > span {
        display: block; height: 100%; width: 0%;
        background: linear-gradient(90deg, #0C403E 0%, #C9A24B 100%);
        border-radius: 999px;
        transition: width 0.4s ease;
    }
    .qaari-sync-progress.is-fail .qaari-sync-bar > span { background: #dc2626; }
    .qaari-sync-progress-hint {
        margin: 0; font-size: 0.8rem; color: #78716c; line-height: 1.4;
    }
    .qaari-sync-empty { color: #6b7280; font-size: 0.9rem; }
    @media (max-width: 640px) {
        .qaari-sync-item { grid-template-columns: 36px 1fr; }
        .qaari-sync-item-time { grid-column: 2; text-align: left; }
    }
</style>

<div
    class="qaari-sync"
    @if ($isSyncRunning)
        wire:poll.2s="pollSyncProgress"
    @endif
>
    <div class="qaari-sync-card">
        <div class="qaari-sync-head">
            <div>
                <h3 class="qaari-sync-title">Manual ayah sync</h3>
                @if ($metaLine !== '')
                    <p class="qaari-sync-meta">{{ $metaLine }}</p>
                @endif
                <div class="qaari-sync-badges">
                    <span @class([
                        'qaari-sync-badge',
                        'is-ok' => $isSynced && ! $isSyncRunning,
                        'is-bad' => $isFailed && ! $isSyncRunning,
                        'is-warn' => $isSyncRunning || $status === SyncStatus::Syncing || $status === SyncStatus::Pending,
                    ])>
                        @if ($isSyncRunning)
                            auto sync running
                        @elseif ($isManual)
                            Manual
                        @elseif ($isSynced)
                            synced
                        @elseif ($status === SyncStatus::Syncing)
                            syncing
                        @elseif ($isFailed)
                            failed
                        @else
                            {{ $status->value ?? 'pending' }}
                        @endif
                    </span>
                    @if ($isManual)
                        <span class="qaari-sync-badge is-ok">Auto sync locked off</span>
                    @endif
                    @if ($resumeAyah > 1 && ! $isSyncRunning)
                        <span class="qaari-sync-badge is-warn">Resume ayah {{ $resumeAyah }}</span>
                    @endif
                </div>
                @if ($isManual)
                    <p class="qaari-sync-hint" style="margin-top:10px;">
                        Ayahs were marked by hand, so automatic matching stays off for this recitation — even if audio is replaced.
                    </p>
                @endif
            </div>
            <div class="qaari-sync-actions">
                @if ($isManual)
                    <span class="qaari-sync-badge is-ok" title="Automatic matching stays off once ayahs are marked by hand">
                        Auto sync locked off
                    </span>
                @else
                    <button
                        type="button"
                        class="qaari-sync-btn"
                        wire:click="runAutoSync(false)"
                        wire:confirm="Queue automatic matching? After you save any manual marks, auto sync will stay off for this recitation."
                        wire:loading.attr="disabled"
                        @disabled($isSyncRunning)
                    >
                        Run auto sync
                    </button>
                @endif
            </div>
        </div>

        <div class="qaari-sync-body">
            @if ($showProgress && filled($progressLabel))
                <div @class([
                    'qaari-sync-progress',
                    'is-fail' => ($syncProgress['status'] ?? '') === 'failed' || ($isFailed && ! $isSyncRunning),
                ])>
                    <div class="qaari-sync-progress-top">
                        <p class="qaari-sync-progress-label">{{ $progressLabel }}</p>
                        <span class="qaari-sync-progress-pct">{{ $progressPercent }}%</span>
                    </div>
                    <div class="qaari-sync-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressPercent }}">
                        <span style="width: {{ $progressPercent }}%;"></span>
                    </div>
                    @if ($isSyncRunning)
                        <p class="qaari-sync-progress-hint">
                            You can leave this page — matching continues in the background. Come back anytime; this bar will catch up.
                        </p>
                    @endif
                </div>
            @endif

            @if ($record?->sync_error && ! $isSyncRunning)
                <div class="qaari-sync-error">{{ $record->sync_error }}</div>
            @endif

            @if (! $audioUrl)
                <p class="qaari-sync-empty">Add the audio file above first, then you can sync ayahs here.</p>
            @elseif ($ayahRows === [])
                <p class="qaari-sync-empty">We couldn’t find the ayah text for this surah.</p>
            @else
                <div
                    x-data="ayahTimingEditor({
                        src: @js($audioUrl),
                        starts: @js($starts),
                        ayahs: @js($ayahPayload),
                        duration: {{ $durationSeconds }},
                        count: {{ $verseCount }},
                        resumeAyah: {{ $resumeAyah }},
                    })"
                    style="display:grid;gap:16px;"
                >
                    <div class="qaari-sync-focus">
                        <div class="qaari-sync-nav">
                            <button type="button" class="qaari-sync-btn" x-on:click="select(selected - 1)" x-bind:disabled="selected <= 0">←</button>
                            <div class="qaari-sync-step">
                                <strong>Ayah <span x-text="selected + 1"></span> of {{ $verseCount }}</strong>
                                <span>Start: <span x-text="fmt(starts[selected] || 0)"></span></span>
                            </div>
                            <button type="button" class="qaari-sync-btn" x-on:click="select(selected + 1)" x-bind:disabled="selected >= count - 1">→</button>
                        </div>

                        <div class="qaari-sync-ayah" x-text="ayahs[selected]?.t || ''"></div>
                    </div>

                    <div class="qaari-sync-transport">
                        <div class="qaari-sync-row">
                            <button type="button" class="qaari-sync-btn" x-on:click="skip(-1)">Back 1s</button>
                            <button type="button" class="qaari-sync-btn qaari-sync-btn-primary" x-on:click="toggle()" x-text="playing ? 'Pause' : 'Play'"></button>
                            <button type="button" class="qaari-sync-btn" x-on:click="skip(1)">Forward 1s</button>
                            <span class="qaari-sync-clock" x-text="clock + ' / ' + fmt(duration)"></span>
                            <span class="qaari-sync-badge is-warn" x-show="dirty" x-cloak>Unsaved</span>
                        </div>
                        <input
                            type="range"
                            class="qaari-sync-range"
                            min="0"
                            x-bind:max="Math.max(1, duration)"
                            step="0.05"
                            x-bind:value="playhead"
                            x-on:input="seekTo($event.target.value)"
                        >
                        <button type="button" class="qaari-sync-btn qaari-sync-btn-mark" x-on:click="setStartHere()">
                            Mark start here
                        </button>
                        <label class="qaari-sync-check">
                            <input type="checkbox" x-model="autoAdvance">
                            Auto-advance to next ayah
                        </label>
                        <p class="qaari-sync-hint">
                            Tip: play audio, pause at the start of the ayah, tap Mark, then Save when ready.
                        </p>
                    </div>

                    <h4 class="qaari-sync-list-title">All ayahs</h4>
                    <div class="qaari-sync-list" x-ref="list">
                        <template x-for="(ayah, index) in ayahs" :key="ayah.n">
                            <button
                                type="button"
                                class="qaari-sync-item"
                                x-bind:class="{ 'is-active': selected === index }"
                                x-on:click="select(index)"
                            >
                                <span class="qaari-sync-item-num" x-text="ayah.n"></span>
                                <span class="qaari-sync-item-text" x-text="ayah.t"></span>
                                <span class="qaari-sync-item-time" x-text="fmt(starts[index] || 0)"></span>
                            </button>
                        </template>
                    </div>

                    <button
                        type="button"
                        class="qaari-sync-btn qaari-sync-btn-save"
                        x-bind:disabled="saving || !dirty"
                        x-on:click="save()"
                        x-text="saving ? 'Saving…' : 'Save progress'"
                    ></button>
                </div>

                <script>
                    window.ayahTimingEditor = function ayahTimingEditor(config) {
                        const resumeIndex = Math.max(0, Math.min((Number(config.count) || 1) - 1, (Number(config.resumeAyah) || 1) - 1));

                        return {
                            src: config.src,
                            starts: Array.isArray(config.starts) ? config.starts.map((n) => Number(n) || 0) : [],
                            ayahs: config.ayahs || [],
                            duration: Number(config.duration) || 0,
                            count: Number(config.count) || 0,
                            dirty: false,
                            saving: false,
                            selected: resumeIndex,
                            playing: false,
                            autoAdvance: true,
                            clock: '00:00.00',
                            playhead: 0,
                            audio: null,
                            init() {
                                this.audio = new Audio(this.src);
                                this.audio.preload = 'metadata';
                                this.audio.addEventListener('timeupdate', () => this.onTime());
                                this.audio.addEventListener('loadedmetadata', () => {
                                    if (this.audio.duration && isFinite(this.audio.duration)) {
                                        this.duration = this.audio.duration;
                                    }
                                    this.audio.currentTime = this.starts[this.selected] || 0;
                                    this.onTime();
                                });
                                this.audio.addEventListener('play', () => { this.playing = true });
                                this.audio.addEventListener('pause', () => { this.playing = false });
                                this.audio.addEventListener('ended', () => { this.playing = false });
                            },
                            fmt(sec) {
                                const t = Math.max(0, Number(sec) || 0);
                                const msTotal = Math.round(t * 1000);
                                const m = Math.floor(msTotal / 60000);
                                const s = Math.floor((msTotal % 60000) / 1000);
                                const cs = Math.floor((msTotal % 1000) / 10);
                                return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}.${String(cs).padStart(2, '0')}`;
                            },
                            toggle() {
                                if (! this.audio) return;
                                if (this.audio.paused) this.audio.play().catch(() => {});
                                else this.audio.pause();
                            },
                            skip(delta) {
                                if (! this.audio) return;
                                const next = Math.max(0, Math.min(this.audio.duration || this.duration || 0, (this.audio.currentTime || 0) + delta));
                                this.audio.currentTime = next;
                                this.onTime();
                            },
                            seekTo(value) {
                                if (! this.audio) return;
                                const t = Math.max(0, Number(value) || 0);
                                this.audio.currentTime = t;
                                this.playhead = t;
                                this.onTime();
                            },
                            select(index) {
                                this.selected = Math.max(0, Math.min(this.count - 1, index));
                                if (! this.audio) return;
                                // Match staff app: seek only — do not auto-play.
                                this.audio.pause();
                                this.audio.currentTime = this.starts[this.selected] || 0;
                                this.onTime();
                            },
                            setStartHere() {
                                if (! this.audio) return;
                                const t = Math.max(0, Number(this.audio.currentTime.toFixed(3)));
                                this.starts[this.selected] = t;
                                this.enforceOrder();
                                this.dirty = true;
                                if (this.autoAdvance && this.selected < this.starts.length - 1) {
                                    this.selected += 1;
                                }
                            },
                            enforceOrder() {
                                for (let i = 1; i < this.starts.length; i++) {
                                    const prev = Number(this.starts[i - 1]) || 0;
                                    if ((Number(this.starts[i]) || 0) < prev + 0.05) {
                                        this.starts[i] = Number((prev + 0.05).toFixed(3));
                                    }
                                }
                            },
                            onTime() {
                                const t = this.audio?.currentTime || 0;
                                this.playhead = t;
                                this.clock = this.fmt(t);
                            },
                            async save() {
                                if (this.saving) return;
                                this.saving = true;
                                try {
                                    await this.$wire.saveManualTimings(
                                        this.starts.map((n) => Number(Number(n).toFixed(3))),
                                        this.selected + 1,
                                    );
                                    this.dirty = false;
                                } catch (err) {
                                    console.error(err);
                                    alert('Could not save progress. Check your connection and try again.');
                                } finally {
                                    this.saving = false;
                                }
                            },
                        };
                    };
                </script>
            @endif
        </div>
    </div>
</div>
