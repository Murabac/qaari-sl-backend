import './bootstrap';
import Alpine from 'alpinejs';

const STORAGE_KEY = 'qaari.player';

Alpine.store('player', {
    open: false,
    playing: false,
    current: 0,
    duration: 0,
    track: null,
    currentAyah: 0,
    _audio: null,

    init() {
        this._audio = new Audio();
        this._audio.preload = 'metadata';

        this._audio.addEventListener('loadedmetadata', () => {
            this.duration = this._audio.duration || (this.track?.durationSeconds ?? 0);
            this.syncAyah();
        });

        this._audio.addEventListener('timeupdate', () => {
            this.current = this._audio.currentTime || 0;
            this.syncAyah();
            this.persist();
        });

        this._audio.addEventListener('ended', () => {
            this.playing = false;
            this.persist();
        });

        this._audio.addEventListener('play', () => {
            this.playing = true;
        });

        this._audio.addEventListener('pause', () => {
            this.playing = false;
        });

        this.restore();
    },

    play(track) {
        if (!track?.src) {
            return;
        }

        const same = this.track?.id === track.id;

        this.track = {
            id: track.id,
            title: track.title,
            subtitle: track.subtitle,
            src: track.src,
            durationSeconds: track.durationSeconds ?? 0,
            reciterUrl: track.reciterUrl ?? null,
            followUrl: track.followUrl ?? null,
            shareUrl: track.shareUrl ?? null,
            verseCount: track.verseCount ?? 0,
            ayahStarts: Array.isArray(track.ayahStarts) ? track.ayahStarts : [],
        };
        this.open = true;

        if (!same || this._audio.src !== track.src) {
            this._audio.src = track.src;
            this.current = 0;
            this.currentAyah = 0;
        }

        this._audio.play().catch(() => {
            this.playing = false;
        });

        this.persist();
    },

    toggle() {
        if (!this.track) {
            return;
        }

        if (this._audio.paused) {
            this._audio.play().catch(() => {});
        } else {
            this._audio.pause();
        }

        this.persist();
    },

    close() {
        this._audio.pause();
        this.open = false;
        this.persist();
    },

    seek(ratio) {
        if (!this.duration) {
            return;
        }

        const next = Math.max(0, Math.min(1, ratio)) * this.duration;
        this._audio.currentTime = next;
        this.current = next;
        this.syncAyah();
        this.persist();
    },

    seekAyah(index) {
        const starts = this.track?.ayahStarts || [];
        const count = starts.length || this.track?.verseCount || 0;

        if (!count || !this.duration) {
            return;
        }

        const clamped = Math.max(0, Math.min(count - 1, index));

        if (starts.length) {
            this._audio.currentTime = starts[clamped] || 0;
            this.current = starts[clamped] || 0;
            this.currentAyah = clamped;
            this.persist();

            return;
        }

        this.seek(clamped / count);
    },

    skip(seconds) {
        if (!this.track) {
            return;
        }

        const next = Math.max(0, Math.min(this.duration || this._audio.duration || 0, this.current + seconds));
        this._audio.currentTime = next;
        this.current = next;
        this.syncAyah();
        this.persist();
    },

    syncAyah() {
        const starts = this.track?.ayahStarts || [];

        if (starts.length && this.duration) {
            const t = this.current + 0.01;

            // Before the first timed ayah (e.g. during basmala), clear the highlight.
            if (t < (starts[0] ?? 0)) {
                this.currentAyah = -1;

                return;
            }

            let index = 0;

            for (let i = 0; i < starts.length; i++) {
                if (t >= starts[i]) {
                    index = i;
                }
            }

            this.currentAyah = index;

            return;
        }

        const count = this.track?.verseCount || 0;

        if (!count || !this.duration) {
            this.currentAyah = 0;

            return;
        }

        const index = Math.min(count - 1, Math.floor((this.current / this.duration) * count));
        this.currentAyah = Math.max(0, index);
    },

    progress() {
        if (!this.duration) {
            return 0;
        }

        return Math.min(100, (this.current / this.duration) * 100);
    },

    format(seconds) {
        const s = Math.floor(seconds || 0);
        const m = Math.floor(s / 60);
        const r = String(s % 60).padStart(2, '0');

        return `${m}:${r}`;
    },

    async share(url, title) {
        const shareUrl = url || this.track?.shareUrl || window.location.href;
        const shareTitle = title || this.track?.title || document.title;

        try {
            if (navigator.share) {
                await navigator.share({ title: shareTitle, url: shareUrl });

                return;
            }
        } catch {
            // user cancelled or share failed — fall through to clipboard
        }

        try {
            await navigator.clipboard.writeText(shareUrl);
            window.dispatchEvent(new CustomEvent('qaari-toast', { detail: { message: 'copied' } }));
        } catch {
            window.prompt('Copy link:', shareUrl);
        }
    },

    persist() {
        if (!this.track) {
            sessionStorage.removeItem(STORAGE_KEY);

            return;
        }

        sessionStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({
                track: this.track,
                current: this.current,
                open: this.open,
            }),
        );
    },

    restore() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);

            if (!raw) {
                return;
            }

            const saved = JSON.parse(raw);

            if (!saved?.track?.src) {
                return;
            }

            this.track = saved.track;
            this.open = Boolean(saved.open);
            this._audio.src = saved.track.src;
            this.duration = saved.track.durationSeconds || 0;

            const resumeAt = Number(saved.current) || 0;

            this._audio.addEventListener(
                'loadedmetadata',
                () => {
                    this._audio.currentTime = resumeAt;
                    this.current = resumeAt;
                    this.duration = this._audio.duration || this.duration;
                    this.syncAyah();
                },
                { once: true },
            );
        } catch {
            sessionStorage.removeItem(STORAGE_KEY);
        }
    },
});

Alpine.data('followAlong', (config) => ({
    ayahs: config.ayahs || [],
    currentAyah: 0,
    _lastScroll: -1,

    boot() {
        if (config.autoPlay?.src) {
            this.$store.player.play(config.autoPlay);
        }

        this.$watch(
            () => this.$store.player.currentAyah,
            (index) => {
                this.currentAyah = index;
                this.scrollToAyah(index);
            },
        );

        this.currentAyah = this.$store.player.currentAyah || 0;
    },

    scrollToAyah(index) {
        if (index === this._lastScroll) {
            return;
        }

        this._lastScroll = index;
        const el = document.getElementById(`ayah-${(this.ayahs[index]?.n) ?? index + 1}`);

        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    },

    shareTrack() {
        this.$store.player.share(config.autoPlay?.shareUrl || config.autoPlay?.followUrl, config.autoPlay?.title);
    },
}));

window.Alpine = Alpine;
Alpine.start();
