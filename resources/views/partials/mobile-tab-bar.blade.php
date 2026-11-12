@php
    use App\Support\LocaleText;

    $tab = function (string $name) {
        return request()->routeIs($name) || request()->routeIs($name.'.*');
    };
    $homeActive = $tab('home');
    $momentsActive = $tab('moments.index');
    $favActive = $tab('library.favorites');
    $plActive = $tab('library.playlists') || $tab('library.playlists.show');
    $settingsActive = $tab('settings') || $tab('login') || $tab('register') || $tab('privacy') || $tab('account-deletion');
@endphp

<nav
    class="qaari-mobile-tabs flex md:hidden"
    aria-label="{{ LocaleText::nav('Home', 'site.home') }}"
>
    <a href="{{ route('home') }}" class="qaari-mobile-tabs__item {{ $homeActive ? 'is-active' : '' }}">
        <svg class="qaari-mobile-tabs__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linejoin="round" d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z"/></svg>
        <span>{{ LocaleText::nav('Home', 'site.home') }}</span>
    </a>

    @if (config('moments.enabled'))
        <a href="{{ route('moments.index') }}" class="qaari-mobile-tabs__item is-moments {{ $momentsActive ? 'is-active' : '' }}">
            <svg class="qaari-mobile-tabs__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M10 9.2v5.6l5-2.8-5-2.8z" fill="currentColor" stroke="none"/></svg>
            <span>{{ LocaleText::nav('Moments', 'site.moments') }}</span>
        </a>
    @endif

    <a href="{{ auth()->check() ? route('library.favorites') : route('login') }}" class="qaari-mobile-tabs__item {{ $favActive ? 'is-active' : '' }}">
        <svg class="qaari-mobile-tabs__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linejoin="round" d="M12 20s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9z"/></svg>
        <span>{{ LocaleText::nav('Favorites', 'site.favorites') }}</span>
    </a>

    <a href="{{ auth()->check() ? route('library.playlists') : route('login') }}" class="qaari-mobile-tabs__item {{ $plActive ? 'is-active' : '' }}">
        <svg class="qaari-mobile-tabs__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z"/></svg>
        <span>{{ LocaleText::nav('Playlists', 'site.playlists') }}</span>
    </a>

    <a href="{{ route('settings') }}" class="qaari-mobile-tabs__item {{ $settingsActive ? 'is-active' : '' }}">
        <svg class="qaari-mobile-tabs__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2.2" fill="currentColor" stroke="none"/><circle cx="8" cy="17" r="2.2" fill="currentColor" stroke="none"/></svg>
        <span>{{ LocaleText::nav('Settings', 'site.settings') }}</span>
    </a>
</nav>
