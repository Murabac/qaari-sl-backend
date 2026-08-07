@extends('layouts.app', ['solidHeader' => true])

@php
    use App\Support\LocaleText;
    $ayahPayload = $ayahs->map(fn ($a) => [
        'n' => $a->number,
        't' => $a->text_uthmani,
    ])->values();
    $duration = (int) ($recitation->duration ?? 0);
    $reciterName = LocaleText::reciterName($recitation->reciter);
@endphp

@section('title', $title.' · '.__('site.follow_along'))

@section('meta')
    <meta property="og:title" content="{{ $title }} — {{ $reciterName }}">
    <meta property="og:description" content="{{ __('site.share_listen_desc', ['surah' => $title, 'reciter' => $reciterName]) }}">
    <meta property="og:url" content="{{ $shareUrl }}">
    <meta property="og:type" content="music.song">
    <meta name="twitter:card" content="summary">
@endsection

@section('content')
    <section
        class="bg-qaari-deep px-4 pb-28 pt-28 text-qaari-primary-fg sm:px-6"
        x-data="followAlong(@js([
            'ayahs' => $ayahPayload,
            'queue' => $playerQueue,
            'autoPlay' => [
                'id' => $recitation->id,
                'title' => $title,
                'subtitle' => $reciterName,
                'src' => $audioUrl,
                'durationSeconds' => $duration,
                'reciterUrl' => route('reciters.show', $recitation->reciter),
                'followUrl' => $shareUrl,
                'shareUrl' => route('reciters.show', ['reciter' => $recitation->reciter, 'play' => $recitation->id]),
                'verseCount' => $ayahs->count() ?: (int) ($recitation->surah->verse_count ?? 0),
                'ayahStarts' => $ayahStarts,
            ],
        ]))"
        x-init="boot()"
    >
        <div class="mx-auto max-w-3xl text-center">
            <a href="{{ route('reciters.show', $recitation->reciter) }}" class="mb-4 inline-block text-sm font-semibold text-qaari-accent hover:text-qaari-accent-hover">
                ← {{ $reciterName }}
            </a>
            <p class="text-sm font-semibold tracking-wide text-qaari-accent uppercase">{{ __('site.follow_along') }}</p>
            <h1 class="font-display mt-2 text-3xl font-bold sm:text-4xl">{{ $title }}</h1>
            @if ($recitation->surah?->name_arabic)
                <p class="font-arabic mt-2 text-xl text-qaari-primary-fg/70" dir="rtl">{{ $recitation->surah->name_arabic }}</p>
            @endif

            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <button
                    type="button"
                    class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold transition hover:border-qaari-accent hover:text-qaari-accent"
                    x-on:click="shareTrack()"
                >{{ __('site.share') }}</button>

                @auth
                    @if ($isFavorite)
                        <form method="POST" action="{{ route('library.favorites.destroy', $recitation) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full border border-qaari-accent/40 bg-qaari-accent/10 px-4 py-2 text-sm font-semibold text-qaari-accent">
                                {{ __('site.favorited') }}
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('library.favorites.store') }}">
                            @csrf
                            <input type="hidden" name="recitation_id" value="{{ $recitation->id }}">
                            <button type="submit" class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold transition hover:border-qaari-accent hover:text-qaari-accent">
                                {{ __('site.add_favorite') }}
                            </button>
                        </form>
                    @endif

                    @if ($playlists->isNotEmpty())
                        <form method="POST" action="#" x-data="{ pid: '{{ $playlists->first()->id }}' }"
                              x-on:submit.prevent="
                                $el.action = '{{ url('/library/playlists') }}/' + pid + '/items';
                                $el.submit();
                              "
                              class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="recitation_id" value="{{ $recitation->id }}">
                            <select x-model="pid" class="rounded-full border border-white/20 bg-qaari-deep px-3 py-2 text-sm text-qaari-primary-fg">
                                @foreach ($playlists as $playlist)
                                    <option value="{{ $playlist->id }}">{{ $playlist->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold hover:border-qaari-accent hover:text-qaari-accent">
                                {{ __('site.add_to_playlist') }}
                            </button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold hover:border-qaari-accent hover:text-qaari-accent">
                        {{ __('site.login_to_save') }}
                    </a>
                @endauth
            </div>
        </div>

        <div class="mx-auto mt-12 max-w-2xl space-y-6" dir="rtl">
            @forelse ($ayahs as $index => $ayah)
                <button
                    type="button"
                    class="font-arabic scroll-mt-32 block w-full text-center text-2xl leading-[2.2] transition duration-300 sm:text-3xl"
                    x-bind:class="currentAyah === {{ $index }} ? 'text-qaari-accent scale-[1.02]' : 'text-qaari-primary-fg/55 hover:text-qaari-primary-fg/80'"
                    x-bind:data-ayah="{{ $index }}"
                    id="ayah-{{ $ayah->number }}"
                    x-on:click="$store.player.seekAyah({{ $index }})"
                >
                    <span>{{ $ayah->text_uthmani }}</span>
                    <span class="ms-2 align-middle text-sm text-qaari-accent/70">﴿{{ $ayah->number }}﴾</span>
                </button>
            @empty
                <p class="text-center text-qaari-primary-fg/60" dir="ltr">{{ __('site.ayahs_unavailable') }}</p>
            @endforelse
        </div>

        <p class="mx-auto mt-10 max-w-xl text-center text-xs text-qaari-primary-fg/40" dir="ltr">
            {{ ($ayahStarts ?? []) !== [] ? __('site.follow_along_synced_note') : __('site.follow_along_timing_note') }}
            @if (($ayahStarts ?? []) !== [])
                · {{ count($ayahStarts) }} ayahs timed
            @endif
        </p>
    </section>
@endsection
