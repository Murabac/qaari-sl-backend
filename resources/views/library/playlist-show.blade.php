@extends('layouts.app', ['solidHeader' => true])

@php
    use App\Support\LocaleText;

    $playerQueue = $tracks
        ->filter(fn (array $track) => filled($track['audio_url'] ?? null))
        ->map(function (array $track) {
            $recitation = $track['recitation'];
            $surah = $recitation->surah;
            $reciter = $recitation->reciter;
            $title = $surah ? (($surah->number ?? '').'. '.LocaleText::surahName($surah)) : __('site.surah');
            $subtitle = $reciter ? LocaleText::reciterName($reciter) : '';

            return [
                'id' => $recitation->id,
                'title' => $title,
                'subtitle' => $subtitle,
                'src' => $track['audio_url'],
                'durationSeconds' => (int) ($recitation->duration ?? 0),
                'reciterUrl' => $reciter ? route('reciters.show', $reciter) : null,
                'followUrl' => route('follow-along.show', $recitation),
                'shareUrl' => $reciter
                    ? route('reciters.show', ['reciter' => $reciter, 'play' => $recitation->id])
                    : route('follow-along.show', $recitation),
                'verseCount' => (int) ($surah->verse_count ?? 0),
                'ayahStarts' => [],
            ];
        })
        ->values()
        ->all();
@endphp

@section('title', $playlist->name.' · '.__('site.footer_brand'))

@section('content')
    <section class="bg-qaari-bg px-4 pb-16 pt-28 sm:px-6">
        <div class="mx-auto max-w-3xl">
            <a href="{{ route('library.playlists') }}" class="mb-4 inline-block text-sm font-semibold text-qaari-accent hover:text-qaari-accent-hover">
                ← {{ __('site.playlists') }}
            </a>

            <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
                <h1 class="font-display text-3xl font-bold text-qaari-primary">{{ $playlist->name }}</h1>
                <form method="POST" action="{{ route('library.playlists.destroy', $playlist) }}" onsubmit="return confirm(@js(__('site.confirm_delete_playlist')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-semibold text-red-700/80 hover:text-red-700">{{ __('site.delete_playlist') }}</button>
                </form>
            </div>

            <form method="POST" action="{{ route('library.playlists.update', $playlist) }}" class="mb-8 flex flex-col gap-3 sm:flex-row">
                @csrf
                @method('PUT')
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $playlist->name) }}"
                    required
                    class="flex-1 rounded-xl border border-qaari-border bg-qaari-card px-4 py-3 text-sm outline-none ring-qaari-accent focus:ring-2"
                >
                <button type="submit" class="rounded-full border border-qaari-border px-5 py-3 text-sm font-semibold text-qaari-primary hover:bg-qaari-card">
                    {{ __('site.rename') }}
                </button>
            </form>

            @if ($tracks->isEmpty())
                <p class="text-qaari-muted">{{ __('site.playlist_empty') }}</p>
            @else
                <ul class="space-y-2">
                    @foreach ($tracks as $track)
                        @php
                            $recitation = $track['recitation'];
                            $surah = $recitation->surah;
                            $reciter = $recitation->reciter;
                            $title = $surah ? (($surah->number ?? '').'. '.LocaleText::surahName($surah)) : __('site.surah');
                            $subtitle = $reciter ? LocaleText::reciterName($reciter) : '';
                            $duration = (int) ($recitation->duration ?? 0);
                        @endphp
                        <li class="flex items-center gap-3 rounded-2xl bg-qaari-card px-4 py-3 ring-1 ring-qaari-border">
                            <button
                                type="button"
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-qaari-primary text-qaari-primary-fg"
                                @if ($track['audio_url'])
                                    x-on:click="$store.player.play({
                                        id: {{ $recitation->id }},
                                        title: @js($title),
                                        subtitle: @js($subtitle),
                                        src: @js($track['audio_url']),
                                        durationSeconds: {{ $duration }},
                                        reciterUrl: @js($reciter ? route('reciters.show', $reciter) : null),
                                        followUrl: @js(route('follow-along.show', $recitation)),
                                        shareUrl: @js(route('reciters.show', ['reciter' => $reciter, 'play' => $recitation->id])),
                                        verseCount: {{ (int) ($surah->verse_count ?? 0) }},
                                    }, @js($playerQueue))"
                                @endif
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </button>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-qaari-primary">{{ $title }}</p>
                                <p class="truncate text-sm text-qaari-muted">{{ $subtitle }}</p>
                            </div>
                            <form method="POST" action="{{ route('library.playlists.items.destroy', [$playlist, $track['item']]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-qaari-muted hover:text-red-700">{{ __('site.remove') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
@endsection
