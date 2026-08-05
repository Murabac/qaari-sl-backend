import './bootstrap';
import Alpine from 'alpinejs';

const STORAGE_KEY = 'qaari.player';

Alpine.store('player', {
    open: false,
    playing: false,
    current: 0,
    duration: 0,
    track: null,
    _audio: null,
    _raf: null,

    init() {
        this._audio = new Audio();
        this._audio.preload = 'metadata';

        this._audio.addEventListener('loadedmetadata', () => {
            this.duration = this._audio.duration || (this.track?.durationSeconds ?? 0);
        });

        this._audio.addEventListener('timeupdate', () => {
            this.current = this._audio.currentTime || 0;
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
        };
        this.open = true;

        if (!same || this._audio.src !== track.src) {
            this._audio.src = track.src;
            this.current = 0;
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
        this.persist();
    },

    skip(seconds) {
        if (!this.track) {
            return;
        }

        const next = Math.max(0, Math.min(this.duration || this._audio.duration || 0, this.current + seconds));
        this._audio.currentTime = next;
        this.current = next;
        this.persist();
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
                },
                { once: true },
            );
        } catch {
            sessionStorage.removeItem(STORAGE_KEY);
        }
    },
});

window.Alpine = Alpine;
Alpine.start();
