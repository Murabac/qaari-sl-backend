import './bootstrap';
import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';

const STORAGE_KEY = 'qaari.player';

function getSharedAudio() {
    if (!window.__qaariAudio) {
        window.__qaariAudio = new Audio();
        window.__qaariAudio.preload = 'metadata';
    }

    return window.__qaariAudio;
}

function bindAudioEvents(store, audio) {
    if (audio.__qaariBound) {
        return;
    }

    audio.__qaariBound = true;

    audio.addEventListener('loadedmetadata', () => {
        store.duration = audio.duration || (store.track?.durationSeconds ?? 0);
        store.syncAyah();
    });

    audio.addEventListener('timeupdate', () => {
        store.current = audio.currentTime || 0;
        store.syncAyah();
        store.persist();
    });

    audio.addEventListener('ended', () => {
        store.playing = false;
        store.persist();
        store.playNext({ auto: true });
    });

    audio.addEventListener('play', () => {
        store.playing = true;
        store.persist();
    });

    audio.addEventListener('pause', () => {
        store.playing = false;
        store.persist();
    });
}

Alpine.store('player', {
    open: false,
    playing: false,
    current: 0,
    duration: 0,
    track: null,
    queue: [],
    queueIndex: -1,
    currentAyah: 0,
    _audio: null,

    init() {
        this._audio = getSharedAudio();
        bindAudioEvents(this, this._audio);
        this.restore();
    },

    setQueue(queue = [], activeId = null) {
        this.queue = Array.isArray(queue) ? queue.filter((t) => t?.src) : [];
        this.queueIndex = activeId == null
            ? -1
            : this.queue.findIndex((t) => t.id === activeId);
    },

    play(track, queue = null) {
        if (!track?.src) {
            return;
        }

        if (Array.isArray(queue)) {
            this.setQueue(queue, track.id);
        } else if (this.queue.length) {
            const idx = this.queue.findIndex((t) => t.id === track.id);
            this.queueIndex = idx;
        } else {
            this.setQueue([track], track.id);
        }

        const sameId = this.track?.id === track.id;
        const currentBase = (this._audio.currentSrc || this._audio.src || '').split('?')[0];
        const nextBase = String(track.src).split('?')[0];

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

        if (!sameId || currentBase !== nextBase || !this._audio.src) {
            this._audio.src = track.src;
            this.current = 0;
            this.currentAyah = 0;
        }

        this._audio.play().catch(() => {
            this.playing = false;
        });

        this.persist();
    },

    ensurePlaying(track, queue = null) {
        if (!track?.src) {
            return;
        }

        if (this.track?.id === track.id) {
            if (Array.isArray(queue)) {
                this.setQueue(queue, track.id);
            }

            this.open = true;

            if (this._audio.paused) {
                this._audio.play().catch(() => {});
            }

            this.persist();

            return;
        }

        this.play(track, queue);
    },

    hasPrevious() {
        return this.queueIndex > 0;
    },

    hasNext() {
        return this.queueIndex >= 0 && this.queueIndex < this.queue.length - 1;
    },

    playPrevious() {
        if (!this.hasPrevious()) {
            return;
        }

        this.play(this.queue[this.queueIndex - 1], this.queue);
        this.syncFollowAlongPage();
    },

    playNext({ auto = false } = {}) {
        if (!this.hasNext()) {
            if (auto) {
                this.playing = false;
                this.persist();
            }

            return;
        }

        this.play(this.queue[this.queueIndex + 1], this.queue);
        this.syncFollowAlongPage();
    },

    syncFollowAlongPage() {
        const followUrl = this.track?.followUrl;

        if (!followUrl || !window.location.pathname.includes('/listen/')) {
            return;
        }

        if (window.Turbo?.visit) {
            window.Turbo.visit(followUrl, { action: 'replace' });
        }
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
            // cancelled
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
                playing: this.playing && !this._audio?.paused,
                queue: this.queue,
                queueIndex: this.queueIndex,
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

            // Turbo navigations keep the live Audio element — don't interrupt it.
            if (this._audio.src && !this._audio.paused && this.track?.id === saved.track.id) {
                this.open = Boolean(saved.open);
                this.queue = Array.isArray(saved.queue) ? saved.queue : this.queue;
                this.queueIndex = Number.isInteger(saved.queueIndex) ? saved.queueIndex : this.queueIndex;

                return;
            }

            if (this._audio.src && this.track?.id) {
                this.open = Boolean(saved.open ?? this.open);
                this.queue = Array.isArray(saved.queue) ? saved.queue : this.queue;
                this.queueIndex = Number.isInteger(saved.queueIndex) ? saved.queueIndex : this.queueIndex;

                return;
            }

            this.track = saved.track;
            this.open = Boolean(saved.open);
            this.queue = Array.isArray(saved.queue) ? saved.queue : [];
            this.queueIndex = Number.isInteger(saved.queueIndex) ? saved.queueIndex : -1;
            this._audio.src = saved.track.src;
            this.duration = saved.track.durationSeconds || 0;

            const resumeAt = Number(saved.current) || 0;
            const shouldPlay = Boolean(saved.playing);

            this._audio.addEventListener(
                'loadedmetadata',
                () => {
                    this._audio.currentTime = resumeAt;
                    this.current = resumeAt;
                    this.duration = this._audio.duration || this.duration;
                    this.syncAyah();

                    if (shouldPlay) {
                        this._audio.play().catch(() => {});
                    }
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
            this.$store.player.ensurePlaying(config.autoPlay, config.queue || null);
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
window.Turbo = Turbo;

if (!window.__qaariAlpineStarted) {
    window.__qaariAlpineStarted = true;
    Alpine.start();
}

document.addEventListener('turbo:load', () => {
    // Re-bind Alpine on newly injected page nodes after Turbo navigations.
    document.querySelectorAll('[x-data]').forEach((el) => {
        if (!el._x_dataStack) {
            Alpine.initTree(el);
        }
    });
});

document.addEventListener('turbo:before-cache', () => {
    document.querySelectorAll('[x-data]').forEach((el) => {
        if (el.id === 'qaari-web-player-root') {
            return;
        }

        if (el._x_dataStack) {
            Alpine.destroyTree(el);
        }
    });
});
