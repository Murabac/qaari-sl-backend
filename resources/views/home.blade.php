@extends('layouts.app', ['solidHeader' => false])

@php
    use App\Support\LocaleText;
    use App\Support\MediaUrl;
@endphp

@section('title', __('site.footer_brand'))

@section('content')
    {{-- Hero --}}
    <section
        class="relative flex h-[100svh] min-h-[100dvh] items-center justify-center overflow-hidden bg-qaari-deep"
        style="background-image: url('https://images.unsplash.com/photo-1540567736792-f78f6242e4e0?w=1920&h=1080&fit=crop&auto=format'); background-size: cover; background-position: center;"
    >
        <div class="absolute inset-0 bg-qaari-deep/80"></div>
        <div class="qaari-pattern-dark absolute inset-0 opacity-30"></div>

        <div class="relative z-10 mx-auto max-w-3xl px-6 py-28 text-center qaari-fade-up">
            <p class="font-arabic mb-8 text-xl tracking-wide text-qaari-accent/65 sm:text-2xl" dir="rtl">
                بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ
            </p>

            <div class="mb-4 flex justify-center">
                <img src="{{ asset('images/logo.svg') }}" alt="{{ __('site.footer_brand') }}" class="h-20 w-20 drop-shadow-lg">
            </div>

            <h1 class="font-display mb-6 whitespace-pre-line text-4xl font-bold leading-[1.15] text-qaari-primary-fg sm:text-5xl lg:text-[3.6rem]">
                {{ __('site.tagline') }}
            </h1>

            <p class="qaari-fade-up-delay mx-auto mb-12 max-w-xl text-base leading-relaxed text-qaari-accent/85 sm:text-lg">
                {{ __('site.hero_desc') }}
            </p>

            <div class="flex flex-wrap justify-center gap-4">
                <a
                    href="{{ route('reciters.index') }}"
                    class="inline-flex items-center gap-2 rounded-full bg-qaari-accent px-8 py-4 text-sm font-semibold text-qaari-primary transition hover:bg-qaari-accent-hover hover:scale-[1.03]"
                >
                    {{ __('site.browse') }}
                    <svg class="h-4 w-4 {{ LocaleText::isRtl() ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M13 5l7 7-7 7"/></svg>
                </a>

                @if ($listenNow && $listenNow['audio_url'])
                    @php
                        $lnReciter = $listenNow['reciter'];
                        $lnRecitation = $listenNow['recitation'];
                        $lnTitle = $lnRecitation->surah
                            ? (($lnRecitation->surah->number ?? '').'. '.LocaleText::surahName($lnRecitation->surah))
                            : __('site.surah');
                    @endphp
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border border-qaari-primary-fg/30 px-8 py-4 text-sm font-semibold text-qaari-primary-fg transition hover:border-qaari-accent hover:text-qaari-accent"
                        x-on:click="$store.player.play({
                            id: {{ $lnRecitation->id }},
                            title: @js($lnTitle),
                            subtitle: @js(LocaleText::reciterName($lnReciter)),
                            src: @js($listenNow['audio_url']),
                            durationSeconds: {{ (int) ($lnRecitation->duration ?? 0) }},
                            reciterUrl: @js(route('reciters.show', $lnReciter)),
                            followUrl: @js(route('follow-along.show', $lnRecitation)),
                            shareUrl: @js(route('reciters.show', ['reciter' => $lnReciter, 'play' => $lnRecitation->id])),
                            verseCount: {{ (int) ($lnRecitation->surah->verse_count ?? 0) }},
                        })"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        {{ __('site.listen_now') }}
                    </button>
                @endif
            </div>
        </div>

        <div class="absolute bottom-10 left-1/2 flex -translate-x-1/2 flex-col items-center gap-2 opacity-35">
            <div class="h-14 w-px bg-qaari-accent"></div>
            <div class="h-1.5 w-1.5 rounded-full bg-qaari-accent"></div>
        </div>
    </section>

    {{-- Featured --}}
    <section class="bg-qaari-bg px-6 py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mb-12">
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-qaari-accent">{{ __('site.featured') }}</p>
                <h2 class="font-display text-3xl font-bold text-qaari-primary sm:text-4xl">{{ __('site.featured_reciters') }}</h2>
            </div>

            @if ($featured->isEmpty())
                <p class="text-qaari-muted">{{ __('site.no_reciters') }}</p>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featured as $reciter)
                        @php
                            $photo = MediaUrl::temporary('r2', $reciter->photo_url);
                        @endphp
                        <a
                            href="{{ route('reciters.show', $reciter) }}"
                            class="group block overflow-hidden rounded-2xl bg-qaari-card shadow-sm ring-1 ring-qaari-border transition hover:-translate-y-1 hover:shadow-md"
                        >
                            <div class="relative aspect-[4/3] overflow-hidden bg-qaari-primary">
                                @if ($photo)
                                    <img src="{{ $photo }}" alt="{{ LocaleText::reciterName($reciter) }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @else
                                    <div class="flex h-full items-center justify-center">
                                        <img src="{{ asset('images/logo.svg') }}" alt="" class="h-16 w-16 opacity-70">
                                    </div>
                                @endif
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-qaari-deep/80 to-transparent p-4 pt-16">
                                    <p class="font-display text-lg font-semibold text-qaari-primary-fg">{{ LocaleText::reciterName($reciter) }}</p>
                                    @if ($reciter->region)
                                        <p class="text-xs text-qaari-accent/90">{{ $reciter->region }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="p-4">
                                <p class="text-sm text-qaari-muted">
                                    {{ __('site.surahs_available', ['count' => $reciter->approved_recitations_count]) }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-10 text-center">
                    <a href="{{ route('reciters.index') }}" class="inline-flex rounded-full bg-qaari-primary px-6 py-3 text-sm font-semibold text-qaari-primary-fg transition hover:bg-qaari-soft">
                        {{ __('site.all_reciters') }}
                    </a>
                </div>
            @endif
        </div>
    </section>

    {{-- Stats (below hero) --}}
    <section class="bg-qaari-primary qaari-pattern-gold px-6 py-16 text-qaari-primary-fg">
        <div class="mx-auto grid max-w-5xl gap-8 text-center sm:grid-cols-3">
            <div>
                <p class="font-display text-3xl font-bold text-qaari-accent">{{ __('site.stats_reciters', ['count' => $stats['reciters']]) }}</p>
            </div>
            <div>
                <p class="font-display text-3xl font-bold text-qaari-accent">{{ __('site.stats_surahs') }}</p>
            </div>
            <div>
                <p class="font-display text-3xl font-bold text-qaari-accent">{{ __('site.stats_recitations', ['count' => $stats['recitations']]) }}</p>
            </div>
        </div>
    </section>

    @if ($showPartners && $partners->isNotEmpty())
        <section class="bg-qaari-bg px-6 py-16">
            <div class="mx-auto max-w-6xl">
                <div class="mb-10 text-center">
                    <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-qaari-accent">{{ __('site.partners_eyebrow') }}</p>
                    <h2 class="font-display text-3xl font-bold text-qaari-primary sm:text-4xl">{{ __('site.partners_title') }}</h2>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-12">
                    @foreach ($partners as $item)
                        @php $partner = $item['partner']; @endphp
                        @if ($partner->url)
                            <a href="{{ $partner->url }}" target="_blank" rel="noopener noreferrer" class="group flex flex-col items-center gap-3 opacity-80 transition hover:opacity-100" title="{{ $partner->name }}">
                        @else
                            <div class="flex flex-col items-center gap-3 opacity-80">
                        @endif
                            <div class="flex h-20 w-36 items-center justify-center rounded-xl bg-qaari-card p-4 ring-1 ring-qaari-border">
                                @if ($item['logo_url'])
                                    <img src="{{ $item['logo_url'] }}" alt="{{ $partner->name }}" class="max-h-12 max-w-full object-contain">
                                @else
                                    <span class="font-display text-sm font-semibold text-qaari-primary">{{ $partner->name }}</span>
                                @endif
                            </div>
                            <span class="text-xs font-medium text-qaari-muted">{{ $partner->name }}</span>
                        @if ($partner->url)
                            </a>
                        @else
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
