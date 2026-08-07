@extends('layouts.app', ['solidHeader' => true])

@php
    use App\Support\LocaleText;
@endphp

@section('title', LocaleText::reciterName($reciter).' · '.__('site.footer_brand'))

@section('meta')
    <meta property="og:title" content="{{ LocaleText::reciterName($reciter) }} — {{ __('site.footer_brand') }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(LocaleText::reciterBio($reciter) ?: __('site.hero_desc'), 160) }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="profile">
    <meta name="twitter:card" content="summary">
@endsection

@section('content')
    <section class="relative overflow-hidden bg-qaari-primary pt-28 pb-16 text-qaari-primary-fg">
        <div class="qaari-pattern-gold absolute inset-0 opacity-80"></div>
        <div class="relative mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:flex-row sm:items-end sm:px-6">
            <div class="h-40 w-40 shrink-0 overflow-hidden rounded-2xl bg-qaari-deep ring-1 ring-white/10 sm:h-48 sm:w-48">
                @if ($photoUrl)
                    <img src="{{ $photoUrl }}" alt="{{ LocaleText::reciterName($reciter) }}" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full items-center justify-center">
                        <img src="{{ asset('images/logo.svg') }}" alt="" class="h-16 w-16 opacity-70">
                    </div>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <a href="{{ route('reciters.index') }}" class="mb-3 inline-block text-sm font-semibold text-qaari-accent hover:text-qaari-accent-hover">
                    ← {{ __('site.back_to_reciters') }}
                </a>
                <h1 class="font-display text-4xl font-bold sm:text-5xl">{{ LocaleText::reciterName($reciter) }}</h1>
                @if ($reciter->region)
                    <p class="mt-2 text-qaari-accent">{{ $reciter->region }}</p>
                @endif
                <p class="mt-3 text-sm text-qaari-primary-fg/70">
                    {{ __('site.surahs_available', ['count' => $tracks->count()]) }}
                </p>
            </div>
        </div>
    </section>

    <section
        class="bg-qaari-bg px-4 py-12 sm:px-6"
        @if ($autoPlayId)
            x-data
            x-init="
                $nextTick(() => {
                    const track = document.querySelector('[data-recitation-id=\"{{ $autoPlayId }}\"]');
                    if (track) {
                        track.click();
                        track.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            "
        @endif
    >
        <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1fr_1.4fr]">
            <div>
                <h2 class="font-display mb-4 text-2xl font-bold text-qaari-primary">{{ __('site.bio') }}</h2>
                @if ($bio = LocaleText::reciterBio($reciter))
                    <p class="whitespace-pre-line leading-relaxed text-qaari-muted">{{ $bio }}</p>
                @else
                    <p class="text-qaari-muted">—</p>
                @endif
            </div>

            <div>
                <h2 class="font-display mb-4 text-2xl font-bold text-qaari-primary">{{ __('site.recordings') }}</h2>

                @if ($tracks->isEmpty())
                    <p class="text-qaari-muted">{{ __('site.no_recordings') }}</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($tracks as $track)
                            @php
                                $recitation = $track['recitation'];
                                $surah = $recitation->surah;
                                $title = $surah
                                    ? (($surah->number ?? '').'. '.LocaleText::surahName($surah))
                                    : __('site.surah');
                                $duration = (int) ($recitation->duration ?? 0);
                                $mins = intdiv($duration, 60);
                                $secs = str_pad((string) ($duration % 60), 2, '0', STR_PAD_LEFT);
                            @endphp
                            <li class="rounded-2xl bg-qaari-card ring-1 ring-qaari-border">
                                <div class="flex items-center gap-2 px-3 py-2 sm:gap-3 sm:px-4 sm:py-3">
                                    <button
                                        type="button"
                                        data-recitation-id="{{ $recitation->id }}"
                                        class="flex min-w-0 flex-1 items-center gap-3 text-start transition hover:opacity-90 group"
                                        @if ($track['audio_url'])
                                            x-on:click="$store.player.play({
                                                id: {{ $recitation->id }},
                                                title: @js($title),
                                                subtitle: @js(LocaleText::reciterName($reciter)),
                                                src: @js($track['audio_url']),
                                                durationSeconds: {{ $duration }},
                                                reciterUrl: @js(route('reciters.show', $reciter)),
                                                followUrl: @js($track['follow_url']),
                                                shareUrl: @js($track['share_url']),
                                                verseCount: {{ (int) ($surah->verse_count ?? 0) }},
                                                ayahStarts: @js($track['ayah_starts'] ?? []),
                                            })"
                                        @else
                                            disabled
                                        @endif
                                    >
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-qaari-primary text-qaari-primary-fg">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate font-semibold text-qaari-primary">{{ $title }}</span>
                                            @if ($surah?->name_arabic)
                                                <span class="font-arabic block truncate text-sm text-qaari-muted" dir="rtl">{{ $surah->name_arabic }}</span>
                                            @endif
                                        </span>
                                        @if ($duration > 0)
                                            <span class="hidden shrink-0 font-mono text-xs text-qaari-muted sm:inline">{{ $mins }}:{{ $secs }}</span>
                                        @endif
                                    </button>

                                    <a
                                        href="{{ $track['follow_url'] }}"
                                        class="hidden shrink-0 rounded-full px-2 py-1 text-xs font-semibold text-qaari-primary hover:text-qaari-accent sm:inline"
                                        title="{{ __('site.follow_along') }}"
                                    >{{ __('site.follow_along_short') }}</a>

                                    <button
                                        type="button"
                                        class="shrink-0 rounded-full p-2 text-qaari-muted transition hover:text-qaari-primary"
                                        title="{{ __('site.share') }}"
                                        x-on:click="$store.player.share(@js($track['share_url']), @js($title.' — '.LocaleText::reciterName($reciter)))"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 12v7a1 1 0 001 1h14a1 1 0 001-1v-7M16 6l-4-4-4 4M12 2v14"/></svg>
                                    </button>

                                    @auth
                                        @if ($track['is_favorite'])
                                            <form method="POST" action="{{ route('library.favorites.destroy', $recitation) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="shrink-0 p-2 text-qaari-accent" title="{{ __('site.remove_favorite') }}">♥</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('library.favorites.store') }}">
                                                @csrf
                                                <input type="hidden" name="recitation_id" value="{{ $recitation->id }}">
                                                <button type="submit" class="shrink-0 p-2 text-qaari-muted hover:text-qaari-accent" title="{{ __('site.add_favorite') }}">♡</button>
                                            </form>
                                        @endif

                                        @if ($playlists->isNotEmpty())
                                            <details class="relative shrink-0">
                                                <summary class="cursor-pointer list-none p-2 text-qaari-muted hover:text-qaari-primary" title="{{ __('site.add_to_playlist') }}">＋</summary>
                                                <div class="absolute end-0 z-20 mt-1 w-52 rounded-xl bg-qaari-card p-2 shadow-lg ring-1 ring-qaari-border">
                                                    @foreach ($playlists as $playlist)
                                                        <form method="POST" action="{{ route('library.playlists.items.store', $playlist) }}">
                                                            @csrf
                                                            <input type="hidden" name="recitation_id" value="{{ $recitation->id }}">
                                                            <button type="submit" class="block w-full rounded-lg px-3 py-2 text-start text-sm text-qaari-primary hover:bg-qaari-bg">
                                                                {{ $playlist->name }}
                                                            </button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="shrink-0 p-2 text-qaari-muted hover:text-qaari-accent" title="{{ __('site.login_to_save') }}">♡</a>
                                    @endauth
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>
@endsection
