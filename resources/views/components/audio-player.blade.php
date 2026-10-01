@php
    use App\Support\LocaleText;
@endphp

{{-- Full-screen Now Playing (mobile app shell + optional desktop) --}}
<div
    class="qaari-now-playing"
    x-show="$store.player.open && $store.player.expanded"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-full opacity-0"
>
    <div class="qaari-now-playing__bg" aria-hidden="true"></div>
    <div class="qaari-now-playing__inner">
        <div class="qaari-now-playing__top">
            <button
                type="button"
                class="qaari-now-playing__icon-btn"
                x-on:click="$store.player.collapse()"
                aria-label="{{ __('site.close_player') }}"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <p class="qaari-now-playing__eyebrow">{{ __('site.now_playing') }}</p>
            <span class="w-10"></span>
        </div>

        <div class="qaari-now-playing__art">
            <img
                src="{{ asset('images/logo-mark-light.png') }}"
                alt=""
                class="qaari-now-playing__logo"
                x-bind:class="{ 'is-spinning': $store.player.playing }"
            >
        </div>

        <div class="qaari-now-playing__meta">
            <h2 class="qaari-now-playing__title" x-text="$store.player.track?.title || ''"></h2>
            <p class="qaari-now-playing__subtitle" x-text="$store.player.track?.subtitle || ''"></p>
        </div>

        <div class="qaari-now-playing__actions">
            <a
                class="qaari-now-playing__chip"
                x-show="$store.player.track?.followUrl"
                x-cloak
                x-bind:href="$store.player.track?.followUrl || '#'"
            >{{ __('site.follow_along') }}</a>
            <button type="button" class="qaari-now-playing__chip" x-on:click="$store.player.share()">
                {{ __('site.share') }}
            </button>
        </div>

        <div
            class="qaari-now-playing__scrub"
            x-on:click="
                const rect = $el.getBoundingClientRect();
                const x = ($event.clientX - rect.left) / rect.width;
                $store.player.seek({{ LocaleText::isRtl() ? '1 - x' : 'x' }});
            "
        >
            <div class="qaari-now-playing__scrub-track">
                <div
                    class="qaari-now-playing__scrub-fill"
                    x-bind:style="'width:' + $store.player.progress() + '%'"
                ></div>
            </div>
            <div class="qaari-now-playing__times">
                <span x-text="$store.player.format($store.player.current)"></span>
                <span x-text="$store.player.format($store.player.duration)"></span>
            </div>
        </div>

        <div class="qaari-now-playing__controls">
            <button type="button" class="qaari-now-playing__ctrl" x-on:click="$store.player.skip(-10)">{{ __('site.skip_back') }}</button>
            <button
                type="button"
                class="qaari-now-playing__ctrl"
                x-on:click="$store.player.playPrevious()"
                x-bind:disabled="! $store.player.hasPrevious()"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
            </button>
            <button type="button" class="qaari-now-playing__play" x-on:click="$store.player.toggle()">
                <svg x-show="! $store.player.playing" class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                <svg x-show="$store.player.playing" x-cloak class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor"><path d="M6 5h4v14H6zm8 0h4v14h-4z"/></svg>
            </button>
            <button
                type="button"
                class="qaari-now-playing__ctrl"
                x-on:click="$store.player.playNext()"
                x-bind:disabled="! $store.player.hasNext()"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M16 6h2v12h-2zm-3.5 6l-8.5 6V6z"/></svg>
            </button>
            <button type="button" class="qaari-now-playing__ctrl" x-on:click="$store.player.skip(10)">{{ __('site.skip_forward') }}</button>
        </div>
    </div>
</div>

{{-- Mini player bar --}}
<div
    class="qaari-web-player"
    x-show="$store.player.open && ! $store.player.expanded"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
>
    <div class="qaari-web-player__shell">
        <div
            class="qaari-web-player__scrub"
            x-on:click="
                const rect = $el.getBoundingClientRect();
                const x = ($event.clientX - rect.left) / rect.width;
                $store.player.seek({{ LocaleText::isRtl() ? '1 - x' : 'x' }});
            "
        >
            <div
                class="qaari-web-player__scrub-fill"
                x-bind:style="'width:' + $store.player.progress() + '%'"
            ></div>
        </div>

        <div class="qaari-web-player__body">
            <button
                type="button"
                class="qaari-web-player__meta text-start"
                x-on:click="$store.player.expand()"
            >
                <p class="qaari-web-player__title" x-text="$store.player.track?.title || ''"></p>
                <p class="qaari-web-player__subtitle" x-text="$store.player.track?.subtitle || '{{ __('site.now_playing') }}'"></p>
            </button>

            <div class="qaari-web-player__controls">
                <button
                    type="button"
                    class="qaari-web-player__skip hidden sm:inline-flex"
                    x-on:click="$store.player.playPrevious()"
                    x-bind:disabled="! $store.player.hasPrevious()"
                    x-bind:class="{ 'opacity-30 pointer-events-none': ! $store.player.hasPrevious() }"
                    title="{{ __('site.prev_surah') }}"
                    aria-label="{{ __('site.prev_surah') }}"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                </button>
                <button type="button" class="qaari-web-player__skip hidden sm:inline-flex" x-on:click="$store.player.skip(-10)">
                    {{ __('site.skip_back') }}
                </button>
                <button
                    type="button"
                    class="qaari-web-player__play"
                    x-on:click="$store.player.toggle()"
                    x-bind:aria-label="$store.player.playing ? '{{ __('site.pause') }}' : '{{ __('site.play') }}'"
                >
                    <svg x-show="! $store.player.playing" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    <svg x-show="$store.player.playing" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 5h4v14H6zm8 0h4v14h-4z"/></svg>
                </button>
                <button type="button" class="qaari-web-player__skip" x-on:click="$store.player.skip(10)">
                    {{ __('site.skip_forward') }}
                </button>
                <button
                    type="button"
                    class="qaari-web-player__skip hidden sm:inline-flex"
                    x-on:click="$store.player.playNext()"
                    x-bind:disabled="! $store.player.hasNext()"
                    x-bind:class="{ 'opacity-30 pointer-events-none': ! $store.player.hasNext() }"
                    title="{{ __('site.next_surah') }}"
                    aria-label="{{ __('site.next_surah') }}"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 6h2v12h-2zm-3.5 6l-8.5 6V6z"/></svg>
                </button>
            </div>

            <div class="qaari-web-player__time">
                <span x-text="$store.player.format($store.player.current)"></span>
                /
                <span x-text="$store.player.format($store.player.duration)"></span>
            </div>

            <a
                class="hidden rounded-full border border-white/15 px-2.5 py-1 text-[0.65rem] font-semibold text-qaari-accent transition hover:border-qaari-accent sm:inline"
                x-show="$store.player.track?.followUrl"
                x-cloak
                x-bind:href="$store.player.track?.followUrl || '#'"
            >{{ __('site.follow_along_short') }}</a>

            <button
                type="button"
                class="hidden rounded-full border border-white/15 px-2.5 py-1 text-[0.65rem] font-semibold text-white/70 transition hover:border-white/40 hover:text-white sm:inline"
                x-on:click="$store.player.share()"
            >{{ __('site.share') }}</button>

            <button
                type="button"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/15 text-white/60 transition hover:border-white/40 hover:text-white"
                x-on:click="$store.player.close()"
                aria-label="{{ __('site.close_player') }}"
                title="{{ __('site.close_player') }}"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>
    </div>
</div>
