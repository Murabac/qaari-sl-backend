@php
    use App\Enums\SyncStatus;
    /** @var \App\Models\Recitation|null $record */
    /** @var list<array{n:int,t:string}> $ayahRows */
    /** @var list<array{ayah_number:int,start_ms:int,end_ms:int}> $timingRows */
    $ayahRows = $ayahRows ?? [];
    $timingRows = $timingRows ?? [];
    $audioUrl = $audioUrl ?? null;
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

    $ayahPayload = $ayahRows !== []
        ? $ayahRows
        : collect(range(1, $verseCount))->map(fn (int $n) => ['n' => $n, 't' => ''])->all();

    $isSynced = $status === SyncStatus::Synced;
    $isFailed = $status === SyncStatus::Failed;
    $resumeAyah = max(1, min($verseCount, (int) ($record?->manual_sync_ayah ?: 1)));
    $isManual = $record?->sync_method === 'manual';
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
        align-items: center;
        justify-content: space-between;
    }
    .qaari-sync-title { margin: 0; font-size: 1.05rem; font-weight: 700; }
    .qaari-sync-sub { margin: 4px 0 0; font-size: 0.875rem; color: #6b7280; line-height: 1.4; max-width: 42rem; }
    .qaari-sync-badge {
        display: inline-flex; align-items: center; border-radius: 999px;
        padding: 4px 12px; font-size: 0.75rem; font-weight: 700;
        background: #f3f4f6; color: #374151;
    }
    .qaari-sync-badge.is-ok { background: #d1fae5; color: #065f46; }
    .qaari-sync-badge.is-bad { background: #fee2e2; color: #991b1b; }
    .qaari-sync-badge.is-warn { background: #fef3c7; color: #92400e; }
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
    .qaari-sync-step {
        text-align: center; flex: 1;
    }
    .qaari-sync-step strong { display: block; font-size: 0.95rem; color: #92400e; }
    .qaari-sync-step span { font-size: 0.8rem; color: #78716c; }
    .qaari-sync-ayah {
        text-align: center;
        direction: rtl;
        font-family: "Amiri", "Scheherazade New", "Noto Naskh Arabic", serif;
        font-size: clamp(1.35rem, 2.5vw, 1.85rem);
        line-height: 2.15;
        color: #111827;
        min-height: 5.5rem;
        padding: 8px 4px;
    }
    .qaari-sync-ayah-no { margin-top: 8px; text-align: center; color: #78716c; font-size: 0.9rem; direction: ltr; }
    .qaari-sync-btn {
        appearance: none; border: 1px solid #d1d5db; background: #fff; color: #111827;
        border-radius: 12px; padding: 10px 14px; font-size: 0.875rem; font-weight: 700;
        cursor: pointer; line-height: 1.2;
    }
    .qaari-sync-btn:hover { background: #f9fafb; }
    .qaari-sync-btn:disabled { opacity: 0.45; cursor: not-allowed; }
    .qaari-sync-btn-primary { background: #0f766e; border-color: #0f766e; color: #fff; }
    .qaari-sync-btn-primary:hover { background: #0d9488; }
    .qaari-sync-btn-mark {
        background: #d97706; border-color: #d97706; color: #fff;
        width: 100%; padding: 16px 18px; font-size: 1.05rem; border-radius: 14px;
    }
    .qaari-sync-btn-mark:hover { background: #f59e0b; }
    .qaari-sync-btn-save {
        background: #059669; border-color: #059669; color: #fff;
        width: 100%; padding: 14px 18px; font-size: 1rem; border-radius: 14px;
    }
    .qaari-sync-btn-save:hover { background: #10b981; }
    .qaari-sync-btn-save:disabled { background: #a7f3d0; border-color: #a7f3d0; color: #065f46; }
    .qaari-sync-transport {
        display: grid; gap: 12px; padding: 16px; border: 1px solid #e5e7eb;
        border-radius: 14px; background: #f8fafc;
    }
    .qaari-sync-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .qaari-sync-clock { font-variant-numeric: tabular-nums; font-weight: 700; font-size: 0.95rem; }
    .qaari-sync-range { width: 100%; accent-color: #0f766e; height: 28px; }
    .qaari-sync-hint {
        margin: 0; font-size: 0.85rem; color: #6b7280; background: #f3f4f6;
        border-radius: 12px; padding: 12px 14px; line-height: 1.45;
    }
    .qaari-sync-hint ol { margin: 8px 0 0; padding-left: 1.2rem; }
    .qaari-sync-hint li { margin: 4px 0; }
    .qaari-sync-dirty { color: #b45309; font-size: 0.8rem; font-weight: 700; }
    .qaari-sync-advanced {
        border-top: 1px solid #e5e7eb; padding-top: 12px; margin-top: 4px;
    }
    .qaari-sync-advanced summary {
        cursor: pointer; font-weight: 700; font-size: 0.85rem; color: #4b5563; user-select: none;
    }
    .qaari-sync-advanced-body { margin-top: 12px; display: grid; gap: 10px; }
    .qaari-sync-list {
        max-height: 280px; overflow: auto; border: 1px solid #e5e7eb;
        border-radius: 12px; background: #fff;
    }
    .qaari-sync-item {
        display: grid; grid-template-columns: 48px 1fr 88px; gap: 10px;
        width: 100%; text-align: left; border: 0; border-bottom: 1px solid #f3f4f6;
        background: #fff; padding: 12px 14px; cursor: pointer; align-items: start;
    }
    .qaari-sync-item:last-child { border-bottom: 0; }
    .qaari-sync-item:hover { background: #f9fafb; }
    .qaari-sync-item.is-active { background: #fffbeb; }
    .qaari-sync-item.is-live { background: #ecfeff; }
    .qaari-sync-item-num { font-weight: 800; color: #6b7280; padding-top: 4px; }
    .qaari-sync-item-text {
        direction: rtl; text-align: right; font-family: "Amiri", "Noto Naskh Arabic", serif;
        font-size: 1.05rem; line-height: 1.8; color: #111827;
    }
    .qaari-sync-item-time {
        font-variant-numeric: tabular-nums; font-size: 0.75rem; color: #6b7280;
        text-align: right; padding-top: 6px;
    }
    .qaari-sync-error {
        background: #fef2f2; color: #991b1b; border-radius: 12px; padding: 12px 14px; font-size: 0.875rem;
    }
    .qaari-sync-empty { color: #6b7280; font-size: 0.9rem; }
    .qaari-sync-jump { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .qaari-sync-jump input {
        width: 88px; border: 1px solid #d1d5db; border-radius: 10px; padding: 8px 10px; font-size: 0.875rem;
    }
    .qaari-sync-check { display: inline-flex; gap: 8px; align-items: center; font-size: 0.85rem; color: #4b5563; }
    @media (max-width: 640px) {
        .qaari-sync-item { grid-template-columns: 36px 1fr; }
        .qaari-sync-item-time { grid-column: 2; text-align: left; }
    }
</style>

<div class="qaari-sync">
    <div class="qaari-sync-card">
        <div class="qaari-sync-head">
            <div>
                <h3 class="qaari-sync-title">Help listeners follow along</h3>
                <p class="qaari-sync-sub">
                    Tell the app when each ayah begins in the recording. You can do a little at a time —
                    save, leave, and pick up later from the same place.
                </p>
            </div>
            <span @class([
                'qaari-sync-badge',
                'is-ok' => $isSynced,
                'is-bad' => $isFailed,
                'is-warn' => $status === SyncStatus::Syncing,
            ])>
                @if ($isManual && $resumeAyah > 1)
                    Continue from ayah {{ $resumeAyah }}
                @elseif ($isSynced)
                    Ready for listeners
                @elseif ($status === SyncStatus::Syncing)
                    Working…
                @elseif ($isFailed)
                    Something went wrong
                @else
                    Not set up yet
                @endif
            </span>
        </div>

        <div class="qaari-sync-body">
            @if ($record?->sync_error)
                <div class="qaari-sync-error">{{ $record->sync_error }}</div>
            @endif

            @if (! $audioUrl)
                <p class="qaari-sync-empty">Add the audio file above first, then you can match the text here.</p>
            @elseif ($ayahs->isEmpty())
                <p class="qaari-sync-empty">We couldn’t find the ayah text for this surah.</p>
            @else
                @if ($isManual)
                    <p class="qaari-sync-hint">
                        <strong>Your hand-marked ayahs are safe.</strong>
                        We won’t replace them with automatic matching.
                        Keep adjusting here and tap <strong>Save progress</strong> when you’re ready.
                    </p>
                @endif

                <div
                    wire:ignore
                    x-data="ayahTimingEditor({
                        src: @js($audioUrl),
                        starts: @js($starts),
                        ayahs: @js($ayahPayload),
                        duration: {{ $durationSeconds }},
                        count: {{ $verseCount }},
                        resumeAyah: {{ $resumeAyah }},
                    })"
                    x-on:keydown.window="onKey($event)"
                    style="display:grid;gap:16px;"
                >
                    <p class="qaari-sync-hint">
                        <strong>Quick steps</strong>
                        <ol>
                            <li>Tap <strong>Play</strong> and listen.</li>
                            <li>When you hear this ayah begin, tap <strong>This ayah starts here</strong>.</li>
                            <li>Tap <strong>Save progress</strong> whenever you like — you can finish the rest another day.</li>
                        </ol>
                    </p>

                    <div class="qaari-sync-focus">
                        <div class="qaari-sync-nav">
                            <button type="button" class="qaari-sync-btn" x-on:click="select(selected - 1)" x-bind:disabled="selected <= 0">← Previous</button>
                            <div class="qaari-sync-step">
                                <strong>Ayah <span x-text="selected + 1"></span> of {{ $verseCount }}</strong>
                                <span>Begins at <span x-text="fmt(starts[selected] || 0)"></span></span>
                            </div>
                            <button type="button" class="qaari-sync-btn" x-on:click="select(selected + 1)" x-bind:disabled="selected >= count - 1">Next →</button>
                        </div>

                        <div class="qaari-sync-ayah" x-text="ayahs[selected]?.t || ''"></div>
                        <div class="qaari-sync-ayah-no">﴿<span x-text="ayahs[selected]?.n || (selected + 1)"></span>﴾</div>
                    </div>

                    <div class="qaari-sync-transport">
                        <div class="qaari-sync-row">
                            <button type="button" class="qaari-sync-btn qaari-sync-btn-primary" x-on:click="toggle()" x-text="playing ? 'Pause' : 'Play'"></button>
                            <button type="button" class="qaari-sync-btn" x-on:click="skip(-1)">Back a little</button>
                            <button type="button" class="qaari-sync-btn" x-on:click="skip(1)">Forward a little</button>
                            <span class="qaari-sync-clock" x-text="clock + ' / ' + fmt(duration)"></span>
                            <span class="qaari-sync-dirty" x-show="dirty" x-cloak>Not saved yet</span>
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
                            This ayah starts here
                        </button>
                        <label class="qaari-sync-check">
                            <input type="checkbox" x-model="autoAdvance">
                            After I mark one, show the next ayah
                        </label>
                        <button
                            type="button"
                            class="qaari-sync-btn qaari-sync-btn-save"
                            x-bind:disabled="saving || !dirty"
                            x-on:click="save()"
                            x-text="saving ? 'Saving…' : 'Save progress'"
                        ></button>
                        <p style="margin:0;font-size:0.8rem;color:#6b7280;">
                            Your work is kept even if you only finish part of the surah. Next time, we’ll bring you back to this ayah.
                        </p>
                    </div>

                    <details class="qaari-sync-advanced">
                        <summary>Need a small fix?</summary>
                        <div class="qaari-sync-advanced-body">
                            <div class="qaari-sync-row">
                                <button type="button" class="qaari-sync-btn" x-on:click="nudge(-0.5)">A bit earlier</button>
                                <button type="button" class="qaari-sync-btn" x-on:click="nudge(0.5)">A bit later</button>
                            </div>
                            <div class="qaari-sync-jump">
                                <span style="font-size:0.85rem;font-weight:700;color:#4b5563;">Go to ayah</span>
                                <input type="number" min="1" max="{{ $verseCount }}" x-model.number="jumpTo" x-on:keydown.enter.prevent="select(Math.min(count, Math.max(1, jumpTo || 1)) - 1)">
                                <button type="button" class="qaari-sync-btn" x-on:click="select(Math.min(count, Math.max(1, jumpTo || 1)) - 1)">Go</button>
                            </div>
                            <button type="button" class="qaari-sync-btn" x-on:click="showList = !showList" x-text="showList ? 'Hide full list' : 'Browse all ayahs'"></button>
                            <div class="qaari-sync-list" x-show="showList" x-cloak x-ref="list">
                                <template x-for="(ayah, index) in ayahs" :key="ayah.n">
                                    <button
                                        type="button"
                                        class="qaari-sync-item"
                                        x-bind:class="{
                                            'is-active': selected === index,
                                            'is-live': liveIndex === index && selected !== index
                                        }"
                                        x-on:click="select(index)"
                                    >
                                        <span class="qaari-sync-item-num" x-text="ayah.n"></span>
                                        <span class="qaari-sync-item-text" x-text="ayah.t"></span>
                                        <span class="qaari-sync-item-time" x-text="fmt(starts[index] || 0)"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </details>
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
                            liveIndex: resumeIndex,
                            playing: false,
                            autoAdvance: true,
                            showList: false,
                            jumpTo: resumeIndex + 1,
                            clock: '0:00',
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
                                    if (this.selected > 0) {
                                        this.audio.currentTime = this.starts[this.selected] || 0;
                                        this.onTime();
                                    }
                                });
                                this.audio.addEventListener('play', () => { this.playing = true });
                                this.audio.addEventListener('pause', () => { this.playing = false });
                                this.audio.addEventListener('ended', () => { this.playing = false });
                            },
                            fmt(sec) {
                                const t = Math.max(0, Number(sec) || 0);
                                const m = Math.floor(t / 60);
                                const s = Math.floor(t % 60);
                                return `${m}:${String(s).padStart(2, '0')}`;
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
                                this.jumpTo = this.selected + 1;
                                if (! this.audio) return;
                                this.audio.currentTime = this.starts[this.selected] || 0;
                                this.liveIndex = this.selected;
                                this.onTime();
                                this.audio.play().catch(() => {});
                            },
                            setStartHere() {
                                if (! this.audio) return;
                                const t = Math.max(0, Number(this.audio.currentTime.toFixed(3)));
                                this.starts[this.selected] = t;
                                this.enforceOrder(this.selected);
                                this.dirty = true;
                                if (this.autoAdvance && this.selected < this.starts.length - 1) {
                                    this.selected += 1;
                                    this.jumpTo = this.selected + 1;
                                }
                            },
                            nudge(delta) {
                                const cur = Number(this.starts[this.selected]) || 0;
                                this.starts[this.selected] = Math.max(0, Number((cur + delta).toFixed(3)));
                                this.enforceOrder(this.selected);
                                this.dirty = true;
                                if (this.audio) {
                                    this.audio.currentTime = this.starts[this.selected];
                                    this.onTime();
                                }
                            },
                            enforceOrder(fromIndex) {
                                for (let i = fromIndex; i < this.starts.length; i++) {
                                    const prev = i === 0 ? 0 : (Number(this.starts[i - 1]) || 0);
                                    const min = i === 0 ? 0 : prev + 0.05;
                                    if ((Number(this.starts[i]) || 0) < min) {
                                        this.starts[i] = Number(min.toFixed(3));
                                    }
                                }
                                for (let i = fromIndex; i >= 0; i--) {
                                    const next = i === this.starts.length - 1
                                        ? (this.duration || Number(this.starts[i]) || 0)
                                        : (Number(this.starts[i + 1]) || 0);
                                    const max = i === this.starts.length - 1
                                        ? Math.max(0, (this.duration || next) - 0.05)
                                        : Math.max(0, next - 0.05);
                                    if ((Number(this.starts[i]) || 0) > max) {
                                        this.starts[i] = Number(max.toFixed(3));
                                    }
                                }
                            },
                            onTime() {
                                const t = this.audio?.currentTime || 0;
                                this.playhead = t;
                                this.clock = this.fmt(t);
                                let idx = 0;
                                for (let i = 0; i < this.starts.length; i++) {
                                    if (t + 0.01 >= (this.starts[i] || 0)) idx = i;
                                }
                                this.liveIndex = idx;
                            },
                            onKey(e) {
                                if (e.target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;
                                if (e.key === 's' || e.key === 'S') {
                                    e.preventDefault();
                                    this.setStartHere();
                                } else if (e.key === 'ArrowLeft') {
                                    e.preventDefault();
                                    this.skip(-1);
                                } else if (e.key === 'ArrowRight') {
                                    e.preventDefault();
                                    this.skip(1);
                                } else if (e.key === 'ArrowUp') {
                                    e.preventDefault();
                                    this.select(this.selected - 1);
                                } else if (e.key === 'ArrowDown') {
                                    e.preventDefault();
                                    this.select(this.selected + 1);
                                }
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
