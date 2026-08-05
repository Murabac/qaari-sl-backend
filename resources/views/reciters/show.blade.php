@extends('layouts.app', ['solidHeader' => true])

@php
    use App\Support\LocaleText;
@endphp

@section('title', LocaleText::reciterName($reciter).' · '.__('site.footer_brand'))

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

    <section class="bg-qaari-bg px-4 py-12 sm:px-6">
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
                            <li>
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-4 rounded-2xl bg-qaari-card px-4 py-3 text-start ring-1 ring-qaari-border transition hover:bg-qaari-primary hover:text-qaari-primary-fg hover:ring-qaari-primary group"
                                    @if ($track['audio_url'])
                                        x-on:click="$store.player.play({
                                            id: {{ $recitation->id }},
                                            title: @js($title),
                                            subtitle: @js(LocaleText::reciterName($reciter)),
                                            src: @js($track['audio_url']),
                                            durationSeconds: {{ $duration }},
                                            reciterUrl: @js(route('reciters.show', $reciter)),
                                        })"
                                    @else
                                        disabled
                                    @endif
                                >
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-qaari-primary text-qaari-primary-fg transition group-hover:bg-qaari-accent group-hover:text-qaari-primary">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-semibold">{{ $title }}</span>
                                        @if ($surah?->name_arabic)
                                            <span class="font-arabic block truncate text-sm opacity-70" dir="rtl">{{ $surah->name_arabic }}</span>
                                        @endif
                                    </span>
                                    @if ($duration > 0)
                                        <span class="shrink-0 font-mono text-xs opacity-60">{{ $mins }}:{{ $secs }}</span>
                                    @endif
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>
@endsection
