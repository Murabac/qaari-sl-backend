@extends('layouts.app', ['solidHeader' => true])

@section('title', __('site.playlists').' · '.__('site.footer_brand'))

@section('content')
    <section class="bg-qaari-bg px-4 pb-16 pt-28 sm:px-6">
        <div class="mx-auto max-w-3xl">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-qaari-accent">{{ __('site.library') }}</p>
                    <h1 class="font-display text-3xl font-bold text-qaari-primary">{{ __('site.playlists') }}</h1>
                </div>
                <a href="{{ route('library.favorites') }}" class="text-sm font-semibold text-qaari-primary hover:text-qaari-accent">
                    {{ __('site.favorites') }} →
                </a>
            </div>

            <form method="POST" action="{{ route('library.playlists.store') }}" class="mb-8 flex flex-col gap-3 sm:flex-row">
                @csrf
                <input
                    type="text"
                    name="name"
                    required
                    maxlength="255"
                    placeholder="{{ __('site.playlist_name_placeholder') }}"
                    class="flex-1 rounded-xl border border-qaari-border bg-qaari-card px-4 py-3 text-sm outline-none ring-qaari-accent focus:ring-2"
                >
                <button type="submit" class="rounded-full bg-qaari-primary px-5 py-3 text-sm font-semibold text-qaari-primary-fg">
                    {{ __('site.create_playlist') }}
                </button>
            </form>
            @error('name')<p class="mb-4 text-sm text-red-700">{{ $message }}</p>@enderror

            @if ($playlists->isEmpty())
                <p class="text-qaari-muted">{{ __('site.playlists_empty') }}</p>
            @else
                <ul class="space-y-2">
                    @foreach ($playlists as $playlist)
                        <li>
                            <a
                                href="{{ route('library.playlists.show', $playlist) }}"
                                class="flex items-center justify-between rounded-2xl bg-qaari-card px-4 py-4 ring-1 ring-qaari-border transition hover:ring-qaari-primary"
                            >
                                <span class="font-semibold text-qaari-primary">{{ $playlist->name }}</span>
                                <span class="text-sm text-qaari-muted">{{ __('site.tracks_count', ['count' => $playlist->items_count]) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
@endsection
