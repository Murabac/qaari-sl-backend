@extends('layouts.app', ['solidHeader' => true])

@php
    use App\Support\LocaleText;
    use App\Support\MediaUrl;
@endphp

@section('title', __('site.all_reciters').' · '.__('site.footer_brand'))

@section('content')
    <section class="bg-qaari-primary qaari-pattern-gold pt-28 pb-14 text-qaari-primary-fg">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-qaari-accent">{{ __('site.footer_brand') }}</p>
            <h1 class="font-display text-4xl font-bold sm:text-5xl">{{ __('site.all_reciters') }}</h1>
        </div>
    </section>

    <section class="bg-qaari-bg px-4 py-12 sm:px-6">
        <div class="mx-auto max-w-7xl">
            <form method="get" action="{{ route('reciters.index') }}" class="mb-10 flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-qaari-muted" for="q">{{ __('site.search') }}</label>
                    <input
                        id="q"
                        name="q"
                        value="{{ $q }}"
                        placeholder="{{ __('site.search_placeholder') }}"
                        class="w-full rounded-xl border border-qaari-border bg-qaari-card px-4 py-3 text-sm text-qaari-fg outline-none ring-qaari-accent focus:ring-2"
                        dir="{{ LocaleText::isRtl() ? 'rtl' : 'ltr' }}"
                    >
                </div>
                @if ($regions->isNotEmpty())
                    <div class="sm:w-48">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-qaari-muted" for="region">{{ __('site.region') }}</label>
                        <select
                            id="region"
                            name="region"
                            class="w-full rounded-xl border border-qaari-border bg-qaari-card px-4 py-3 text-sm text-qaari-fg outline-none ring-qaari-accent focus:ring-2"
                        >
                            <option value="">{{ __('site.all_reciters') }}</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption }}" @selected($region === $regionOption)>{{ $regionOption }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <button type="submit" class="rounded-full bg-qaari-primary px-6 py-3 text-sm font-semibold text-qaari-primary-fg transition hover:bg-qaari-soft">
                    {{ __('site.search') }}
                </button>
            </form>

            @if ($reciters->isEmpty())
                <p class="text-qaari-muted">{{ __('site.no_reciters') }}</p>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($reciters as $reciter)
                        @php $photo = MediaUrl::temporary('r2', $reciter->photo_url); @endphp
                        <a
                            href="{{ route('reciters.show', $reciter) }}"
                            class="group overflow-hidden rounded-2xl bg-qaari-card ring-1 ring-qaari-border transition hover:-translate-y-1 hover:shadow-md"
                        >
                            <div class="aspect-[4/3] overflow-hidden bg-qaari-primary">
                                @if ($photo)
                                    <img src="{{ $photo }}" alt="{{ LocaleText::reciterName($reciter) }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @else
                                    <div class="flex h-full items-center justify-center">
                                        <img src="{{ asset('images/logo.svg') }}" alt="" class="h-14 w-14 opacity-70">
                                    </div>
                                @endif
                            </div>
                            <div class="p-5">
                                <h2 class="font-display text-xl font-semibold text-qaari-primary">{{ LocaleText::reciterName($reciter) }}</h2>
                                @if ($reciter->region)
                                    <p class="mt-1 text-sm text-qaari-accent">{{ $reciter->region }}</p>
                                @endif
                                @if ($bio = LocaleText::reciterBio($reciter))
                                    <p class="mt-3 line-clamp-2 text-sm text-qaari-muted">{{ $bio }}</p>
                                @endif
                                <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-qaari-muted">
                                    {{ __('site.surahs_available', ['count' => $reciter->approved_recitations_count]) }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $reciters->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
