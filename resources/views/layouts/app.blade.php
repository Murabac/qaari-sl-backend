@php
    use App\Support\LocaleText;
    $locale = LocaleText::locale();
    $isRtl = LocaleText::isRtl();
    $solidHeader = $solidHeader ?? false;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('site.footer_brand'))</title>
    @yield('meta')
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Nunito:wght@600;700;800&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="site-main flex min-h-screen flex-col has-mobile-tabs"
    x-data="{ toast: '' }"
    x-on:qaari-toast.window="toast = '{{ __('site.link_copied') }}'; setTimeout(() => toast = '', 2200)"
    x-bind:class="{
        'has-player': $store.player.open,
        'has-now-playing': $store.player.open && $store.player.expanded,
    }"
>
    <header
        class="site-header site-header--marketing hidden md:block {{ $solidHeader ? 'is-solid' : 'is-transparent' }}"
        x-data="{ scrolled: false, menu: false }"
        x-init="
            const onScroll = () => { scrolled = window.scrollY > 24 };
            onScroll();
            window.addEventListener('scroll', onScroll, { passive: true });
        "
        x-bind:class="{
            'is-scrolled': scrolled && ! {{ $solidHeader ? 'true' : 'false' }},
            'is-solid': {{ $solidHeader ? 'true' : 'false' }},
            'is-transparent': ! scrolled && ! {{ $solidHeader ? 'true' : 'false' }},
        }"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img
                    alt="{{ __('site.footer_brand') }}"
                    class="h-14 w-14 shrink-0 sm:h-16 sm:w-16"
                    width="64"
                    height="64"
                    style="width:3.5rem;height:3.5rem"
                    x-bind:src="(scrolled || {{ $solidHeader ? 'true' : 'false' }})
                        ? '{{ asset('images/logo-mark.png') }}'
                        : '{{ asset('images/logo-mark-light.png') }}'"
                    src="{{ asset($solidHeader ? 'images/logo-mark.png' : 'images/logo-mark-light.png') }}"
                >
                <span
                    class="font-display text-lg font-semibold tracking-tight transition"
                    x-bind:class="(scrolled || {{ $solidHeader ? 'true' : 'false' }}) ? 'text-qaari-primary' : 'text-qaari-primary-fg'"
                >{{ __('site.footer_brand') }}</span>
            </a>

            <nav class="hidden items-center gap-6 lg:flex">
                <a
                    href="{{ route('home') }}"
                    class="text-sm font-semibold transition"
                    x-bind:class="(scrolled || {{ $solidHeader ? 'true' : 'false' }}) ? 'text-qaari-primary hover:text-qaari-accent' : 'text-qaari-primary-fg/90 hover:text-qaari-accent'"
                >{{ __('site.home') }}</a>
                <a
                    href="{{ route('reciters.index') }}"
                    class="text-sm font-semibold transition"
                    x-bind:class="(scrolled || {{ $solidHeader ? 'true' : 'false' }}) ? 'text-qaari-primary hover:text-qaari-accent' : 'text-qaari-primary-fg/90 hover:text-qaari-accent'"
                >{{ __('site.reciters') }}</a>
                <a
                    href="{{ route('story') }}"
                    class="text-sm font-semibold transition"
                    x-bind:class="(scrolled || {{ $solidHeader ? 'true' : 'false' }}) ? 'text-qaari-primary hover:text-qaari-accent' : 'text-qaari-primary-fg/90 hover:text-qaari-accent'"
                >{{ __('site.story') }}</a>
                @auth
                    <a
                        href="{{ route('library.favorites') }}"
                        class="text-sm font-semibold transition"
                        x-bind:class="(scrolled || {{ $solidHeader ? 'true' : 'false' }}) ? 'text-qaari-primary hover:text-qaari-accent' : 'text-qaari-primary-fg/90 hover:text-qaari-accent'"
                    >{{ __('site.favorites') }}</a>
                    <a
                        href="{{ route('library.playlists') }}"
                        class="text-sm font-semibold transition"
                        x-bind:class="(scrolled || {{ $solidHeader ? 'true' : 'false' }}) ? 'text-qaari-primary hover:text-qaari-accent' : 'text-qaari-primary-fg/90 hover:text-qaari-accent'"
                    >{{ __('site.playlists') }}</a>
                @endauth
            </nav>

            <div class="flex items-center gap-2">
                <div
                    class="flex overflow-hidden rounded-full border text-xs font-semibold"
                    x-bind:class="(scrolled || {{ $solidHeader ? 'true' : 'false' }}) ? 'border-qaari-border bg-qaari-card' : 'border-white/20 bg-black/20'"
                >
                    @foreach (['en' => 'EN', 'so' => 'SO', 'ar' => 'AR'] as $code => $label)
                        <a
                            href="{{ route('locale.switch', $code) }}"
                            class="px-2.5 py-1.5 transition {{ $locale === $code ? 'bg-qaari-accent text-qaari-primary' : '' }}"
                            x-bind:class="(scrolled || {{ $solidHeader ? 'true' : 'false' }}) ? 'text-qaari-primary' : 'text-qaari-primary-fg'"
                        >{{ $label }}</a>
                    @endforeach
                </div>

                @auth
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button
                            type="submit"
                            class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
                            x-bind:class="(scrolled || {{ $solidHeader ? 'true' : 'false' }}) ? 'text-qaari-primary hover:text-qaari-accent' : 'text-qaari-primary-fg/90 hover:text-qaari-accent'"
                        >{{ __('site.logout') }}</button>
                    </form>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="hidden rounded-full px-3 py-1.5 text-xs font-semibold transition sm:inline"
                        x-bind:class="(scrolled || {{ $solidHeader ? 'true' : 'false' }}) ? 'text-qaari-primary hover:text-qaari-accent' : 'text-qaari-primary-fg/90 hover:text-qaari-accent'"
                    >{{ __('site.login') }}</a>
                @endauth

                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border lg:hidden"
                    x-bind:class="(scrolled || {{ $solidHeader ? 'true' : 'false' }}) ? 'border-qaari-border text-qaari-primary' : 'border-white/25 text-qaari-primary-fg'"
                    x-on:click="menu = ! menu"
                    aria-label="Menu"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>

        <div
            class="border-t border-qaari-border bg-qaari-bg px-4 py-3 lg:hidden"
            x-show="menu"
            x-cloak
        >
            <a href="{{ route('home') }}" class="block py-2 text-sm font-semibold text-qaari-primary">{{ __('site.home') }}</a>
            <a href="{{ route('reciters.index') }}" class="block py-2 text-sm font-semibold text-qaari-primary">{{ __('site.reciters') }}</a>
            <a href="{{ route('story') }}" class="block py-2 text-sm font-semibold text-qaari-primary">{{ __('site.story') }}</a>
            @auth
                <a href="{{ route('library.favorites') }}" class="block py-2 text-sm font-semibold text-qaari-primary">{{ __('site.favorites') }}</a>
                <a href="{{ route('library.playlists') }}" class="block py-2 text-sm font-semibold text-qaari-primary">{{ __('site.playlists') }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block py-2 text-sm font-semibold text-qaari-primary">{{ __('site.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block py-2 text-sm font-semibold text-qaari-primary">{{ __('site.login') }}</a>
                <a href="{{ route('register') }}" class="block py-2 text-sm font-semibold text-qaari-primary">{{ __('site.register') }}</a>
            @endauth
        </div>
    </header>

    @if (session('status'))
        <div class="fixed inset-x-0 top-20 z-50 mx-auto max-w-md px-4">
            <p class="rounded-xl bg-qaari-primary px-4 py-3 text-center text-sm font-semibold text-qaari-primary-fg shadow-lg">
                {{ session('status') }}
            </p>
        </div>
    @endif

    <div
        class="pointer-events-none fixed inset-x-0 top-20 z-50 mx-auto max-w-xs px-4"
        x-show="toast"
        x-cloak
        x-transition
    >
        <p class="rounded-xl bg-qaari-primary px-4 py-3 text-center text-sm font-semibold text-qaari-primary-fg shadow-lg" x-text="toast"></p>
    </div>

    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.apk-band')

    <footer class="site-footer--marketing mt-auto hidden border-t border-qaari-border bg-qaari-primary text-qaari-primary-fg md:block">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo-mark-light.png') }}" alt="" class="h-12 w-12 shrink-0" width="48" height="48" style="width:3rem;height:3rem">
                <span class="font-display text-lg font-semibold">{{ __('site.footer_brand') }}</span>
            </div>
            <div class="max-w-xl space-y-2">
                <p class="text-sm text-qaari-primary-fg/70">{{ __('site.platform_desc') }}</p>
                <a href="{{ route('privacy') }}" class="inline-block text-sm font-semibold text-qaari-accent hover:text-qaari-accent-hover">
                    {{ __('site.privacy_policy') }}
                </a>
                <span class="mx-2 text-qaari-primary-fg/30">·</span>
                <a href="{{ route('account-deletion') }}" class="inline-block text-sm font-semibold text-qaari-accent hover:text-qaari-accent-hover">
                    {{ __('site.account_deletion') }}
                </a>
            </div>
        </div>
    </footer>

    <div id="qaari-web-player-root" data-turbo-permanent>
        @include('components.audio-player')
    </div>

    @include('partials.mobile-tab-bar')

    <style>[x-cloak]{display:none!important}</style>
</body>
</html>
