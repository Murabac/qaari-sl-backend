@extends('layouts.app', ['solidHeader' => true])

@section('title', __('site.story').' · '.__('site.footer_brand'))

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-qaari-primary pt-28 pb-16 text-qaari-primary-fg">
        <div class="qaari-pattern-gold absolute inset-0 opacity-80"></div>
        <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6">
            <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-qaari-accent">{{ __('site.footer_brand') }}</p>
            <h1 class="font-display text-4xl font-bold sm:text-5xl">{{ __('site.story_title') }}</h1>
            <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-qaari-primary-fg/80 sm:text-lg">
                {{ $settings->hero_mission }}
            </p>
        </div>
    </section>

    {{-- Patrons & Leadership --}}
    <section class="bg-qaari-bg px-4 py-16 sm:px-6 sm:py-20">
        <div class="mx-auto max-w-6xl">
            <div class="mb-14 text-center">
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-qaari-accent">{{ __('site.patrons_eyebrow') }}</p>
                <h2 class="font-display text-3xl font-bold text-qaari-primary sm:text-4xl">{{ __('site.patrons_title') }}</h2>
            </div>

            {{-- Tier 1: President --}}
            @if ($president->isNotEmpty())
                <div class="mb-14 flex flex-col items-center gap-8">
                    @foreach ($president as $item)
                        @include('partials.story-leader-card', [
                            'item' => $item,
                            'size' => 'lg',
                        ])
                    @endforeach
                </div>
            @endif

            {{-- Tier 2: Ministers --}}
            @if ($ministers->isNotEmpty())
                <div class="mb-14 grid gap-8 sm:grid-cols-2 sm:justify-items-center">
                    @foreach ($ministers as $item)
                        @include('partials.story-leader-card', [
                            'item' => $item,
                            'size' => 'md',
                        ])
                    @endforeach
                </div>
            @endif

            {{-- Tier 3: Board --}}
            @if ($board->isNotEmpty())
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-4 h-px w-16 bg-qaari-accent/60"></div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-qaari-muted">
                        {{ __('site.board_members') }}
                    </p>
                </div>

                <div class="flex flex-wrap justify-center gap-6">
                    @foreach ($board as $item)
                        <div class="w-full sm:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)]">
                            @include('partials.story-leader-card', [
                                'item' => $item,
                                'size' => 'board',
                            ])
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($president->isEmpty() && $ministers->isEmpty() && $board->isEmpty())
                <p class="text-center text-qaari-muted">{{ __('site.story_empty_leadership') }}</p>
            @endif
        </div>
    </section>

    {{-- Behind the Voices --}}
    <section class="border-t border-qaari-border bg-qaari-card px-4 py-16 sm:px-6 sm:py-20">
        <div class="mx-auto max-w-5xl">
            <div class="mb-10 text-center">
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-qaari-accent">{{ __('site.team_eyebrow') }}</p>
                <h2 class="font-display text-2xl font-bold text-qaari-primary sm:text-3xl">{{ __('site.team_title') }}</h2>
            </div>

            @if ($team->isEmpty())
                <p class="text-center text-sm text-qaari-muted">{{ __('site.story_empty_team') }}</p>
            @else
                <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($team as $item)
                        @php $member = $item['member']; @endphp
                        <li class="flex items-start gap-3">
                            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-full bg-qaari-primary/10 ring-1 ring-qaari-border">
                                @if ($item['photo_url'])
                                    <img src="{{ $item['photo_url'] }}" alt="{{ $member->name }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-xs font-semibold text-qaari-primary">
                                        {{ mb_substr($member->name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0 pt-0.5">
                                <p class="text-sm font-semibold text-qaari-primary">{{ $member->name }}</p>
                                <p class="text-xs text-qaari-accent">{{ $member->role }}</p>
                                @if ($member->description)
                                    <p class="mt-1 text-xs leading-relaxed text-qaari-muted">{{ $member->description }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- Closing --}}
    <section class="relative overflow-hidden bg-qaari-primary px-4 py-16 text-qaari-primary-fg sm:px-6 sm:py-20">
        <div class="qaari-pattern-gold absolute inset-0 opacity-70"></div>

        <div class="relative mx-auto max-w-3xl rounded-[2rem] border border-qaari-accent/30 bg-qaari-deep/35 px-6 py-10 text-center shadow-[0_24px_60px_rgba(10,28,17,0.2)] sm:px-12 sm:py-12">
            <div class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-full border border-qaari-accent/35 bg-qaari-accent/10 text-qaari-accent">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M7.2 17.5H3.5v-4.1c0-3.6 1.5-6 4.8-7.5l1 1.8c-2 1.1-2.8 2.4-2.9 4.1h2.2v5.7H7.2Zm9.8 0h-3.7v-4.1c0-3.6 1.5-6 4.8-7.5l1 1.8c-2 1.1-2.8 2.4-2.9 4.1h2.2v5.7H17Z"/>
                </svg>
            </div>

            <p class="mb-4 text-[11px] font-semibold uppercase tracking-[0.24em] text-qaari-accent">
                {{ __('site.closing_eyebrow') }}
            </p>

            <blockquote class="font-display mx-auto max-w-2xl text-xl leading-relaxed text-qaari-primary-fg sm:text-2xl">
                {{ $settings->closing_note }}
            </blockquote>

            <div class="mx-auto mt-7 h-px w-16 bg-qaari-accent/60"></div>
        </div>
    </section>
@endsection
