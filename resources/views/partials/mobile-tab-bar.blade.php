<nav
    class="qaari-mobile-tabs flex md:hidden"
    aria-label="{{ __('site.library') }}"
>
    @php
        $tab = function (string $name) {
            return request()->routeIs($name) || request()->routeIs($name.'.*');
        };
        $homeActive = $tab('home');
        $favActive = $tab('library.favorites');
        $plActive = $tab('library.playlists') || $tab('library.playlists.show');
        $settingsActive = $tab('settings') || $tab('login') || $tab('register') || $tab('privacy') || $tab('account-deletion');
    @endphp

    <a
        href="{{ route('home') }}"
        class="qaari-mobile-tabs__item {{ $homeActive ? 'is-active' : '' }}"
    >
        <svg class="qaari-mobile-tabs__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        <span>{{ __('site.home') }}</span>
    </a>

    <a
        href="{{ auth()->check() ? route('library.favorites') : route('login') }}"
        class="qaari-mobile-tabs__item {{ $favActive ? 'is-active' : '' }}"
    >
        <svg class="qaari-mobile-tabs__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
        <span>{{ __('site.favorites') }}</span>
    </a>

    <a
        href="{{ auth()->check() ? route('library.playlists') : route('login') }}"
        class="qaari-mobile-tabs__item {{ $plActive ? 'is-active' : '' }}"
    >
        <svg class="qaari-mobile-tabs__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z"/></svg>
        <span>{{ __('site.playlists') }}</span>
    </a>

    <a
        href="{{ route('settings') }}"
        class="qaari-mobile-tabs__item {{ $settingsActive ? 'is-active' : '' }}"
    >
        <svg class="qaari-mobile-tabs__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.49.49 0 0 0-.59-.22l-2.39.96a7.1 7.1 0 0 0-1.62-.94l-.36-2.54a.48.48 0 0 0-.48-.41h-3.84a.48.48 0 0 0-.48.41l-.36 2.54c-.57.23-1.11.54-1.62.94l-2.39-.96a.49.49 0 0 0-.59.22L2.77 8.87a.49.49 0 0 0 .12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.4 1.05.71 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.48-.41l.36-2.54c.57-.23 1.11-.54 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.49.49 0 0 0-.12-.61l-2.03-1.58zM12 15.6A3.6 3.6 0 1 1 12 8.4a3.6 3.6 0 0 1 0 7.2z"/></svg>
        <span>{{ __('site.settings') }}</span>
    </a>
</nav>
