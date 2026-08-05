@php
    /** @var \App\Models\Recitation $record */
    $durationLabel = $record->duration
        ? sprintf('%d:%02d', intdiv($record->duration, 60), $record->duration % 60)
        : '—';
@endphp

<div class="qaari-player">
    <div class="qaari-player__meta">
        <p class="qaari-player__surah">
            {{ $record->surah?->number }}. {{ $record->surah?->name_english }}
            <span class="qaari-player__surah-ar">{{ $record->surah?->name_arabic }}</span>
        </p>
        <p class="qaari-player__reciter">{{ $record->reciter?->name_english }}</p>
        <p class="qaari-player__sub">
            Uploaded by {{ $record->creator?->name ?? '—' }}
            @if ($record->submitted_at)
                · submitted {{ $record->submitted_at->diffForHumans() }}
            @endif
        </p>
    </div>

    <div
        class="qaari-player__shell"
        x-data="{
            src: @js($audioUrl),
            playing: false,
            progress: 0,
            current: 0,
            duration: 0,
            error: null,
            fmt(s) {
                s = Math.floor(s || 0);
                return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
            },
            init() {
                if (! this.src) {
                    this.error = 'Audio file is missing for this recitation.';
                    return;
                }

                const audio = this.$refs.audio;

                audio.addEventListener('timeupdate', () => {
                    this.current = audio.currentTime;
                    if (audio.duration) {
                        this.duration = audio.duration;
                        this.progress = (audio.currentTime / audio.duration) * 100;
                    }
                });
                audio.addEventListener('loadedmetadata', () => {
                    this.duration = audio.duration || 0;
                });
                audio.addEventListener('play', () => { this.playing = true; });
                audio.addEventListener('pause', () => { this.playing = false; });
                audio.addEventListener('ended', () => {
                    this.playing = false;
                    this.progress = 0;
                    this.current = 0;
                });
                audio.addEventListener('error', () => {
                    this.error = 'Could not load the audio file.';
                    this.playing = false;
                });
            },
            toggle() {
                const audio = this.$refs.audio;
                if (! audio || ! this.src) return;

                if (audio.paused) {
                    this.error = null;
                    audio.play().catch(() => {
                        this.error = 'Playback was blocked by the browser.';
                    });
                } else {
                    audio.pause();
                }
            },
            seek(event) {
                const audio = this.$refs.audio;
                if (! audio || ! this.duration) return;
                const rect = event.currentTarget.getBoundingClientRect();
                const pct = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
                audio.currentTime = pct * this.duration;
                this.progress = pct * 100;
            },
            skip(seconds) {
                const audio = this.$refs.audio;
                if (! audio) return;
                const max = this.duration || audio.duration || 0;
                audio.currentTime = Math.max(0, Math.min(max, audio.currentTime + seconds));
            },
        }"
        x-on:close-modal.window="$refs.audio && $refs.audio.pause()"
    >
        <audio x-ref="audio" preload="metadata" src="{{ $audioUrl }}" class="hidden"></audio>

        <div class="qaari-player__scrub" x-on:click="seek($event)" role="slider" aria-label="Seek">
            <div class="qaari-player__scrub-fill" x-bind:style="`width: ${progress}%`"></div>
            <div class="qaari-player__scrub-knob" x-bind:style="`left: calc(${progress}% - 6px)`"></div>
        </div>

        <div class="qaari-player__body">
            <div class="qaari-player__track">
                <div class="qaari-player__wave" x-bind:class="playing ? 'is-active' : ''">
                    @foreach ([3, 5, 4, 7, 3, 6, 4, 3, 7, 4, 5, 3] as $h)
                        <span style="--h: {{ $h }}"></span>
                    @endforeach
                </div>
                <div class="qaari-player__titles">
                    <p class="qaari-player__title">{{ $record->surah?->name_english }}</p>
                    <p class="qaari-player__subtitle">{{ $record->reciter?->name_english }}</p>
                </div>
            </div>

            <div class="qaari-player__controls">
                <button type="button" class="qaari-player__skip" x-on:click="skip(-10)" aria-label="Back 10 seconds">
                    −10s
                </button>
                <button type="button" class="qaari-player__play" x-on:click="toggle()" aria-label="Play or pause">
                    <svg x-show="! playing" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                        <path d="M8 5.14v14l11-7-11-7z"/>
                    </svg>
                    <svg x-show="playing" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                        <path d="M6 5h4v14H6V5zm8 0h4v14h-4V5z"/>
                    </svg>
                </button>
                <button type="button" class="qaari-player__skip" x-on:click="skip(10)" aria-label="Forward 10 seconds">
                    +10s
                </button>
            </div>

            <div class="qaari-player__right">
                <span class="qaari-player__time" x-text="fmt(current)">0:00</span>
                <span class="qaari-player__duration">{{ $durationLabel }}</span>
            </div>
        </div>

        <p class="qaari-player__error" x-show="error" x-text="error" x-cloak></p>
    </div>
</div>
