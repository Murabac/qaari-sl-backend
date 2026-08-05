@php
    use App\Support\LocaleText;
@endphp

<div
    class="qaari-web-player"
    x-show="$store.player.open"
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
            <div class="qaari-web-player__meta">
                <p class="qaari-web-player__title" x-text="$store.player.track?.title || ''"></p>
                <p class="qaari-web-player__subtitle" x-text="$store.player.track?.subtitle || '{{ __('site.now_playing') }}'"></p>
            </div>

            <div class="qaari-web-player__controls">
                <button type="button" class="qaari-web-player__skip" x-on:click="$store.player.skip(-10)">
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
            </div>

            <div class="qaari-web-player__time">
                <span x-text="$store.player.format($store.player.current)"></span>
                /
                <span x-text="$store.player.format($store.player.duration)"></span>
            </div>

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
