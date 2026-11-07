@extends('layouts.app', ['solidHeader' => true, 'hideApk' => true])

@php
    use App\Support\LocaleText;
    use App\Support\MediaUrl;
    $filters = [
        'latest' => __('site.moments_latest'),
        'trending' => __('site.moments_trending'),
        'following' => __('site.moments_following'),
        'saved' => __('site.moments_saved'),
    ];
@endphp

@section('title', LocaleText::nav('Moments', 'site.moments').' · '.__('site.footer_brand'))

@section('content')
    <section class="min-h-screen bg-[#08281F] px-4 pb-8 pt-4 text-[#F5F2EA] md:pt-28" x-data>
        <div class="mx-auto max-w-lg">
            <header class="mb-4 flex items-center gap-3">
                <img src="{{ asset('images/logo-mark-light.png') }}" alt="" class="h-10 w-10" width="40" height="40">
                <h1 class="text-lg font-extrabold">{{ LocaleText::nav('Moments', 'site.moments') }}</h1>
            </header>

            <form method="GET" action="{{ route('moments.index') }}" class="mb-4">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="{{ __('site.moments_search') }}"
                    class="w-full rounded-2xl border-0 bg-white/10 px-4 py-3 text-sm font-semibold text-[#F5F2EA] outline-none placeholder:text-[#A2B5AA]"
                >
            </form>

            <div class="mb-5 flex gap-2 overflow-x-auto pb-1">
                @foreach ($filters as $key => $label)
                    <a
                        href="{{ route('moments.index', array_filter(['filter' => $key, 'q' => $search])) }}"
                        class="shrink-0 rounded-full px-3 py-1.5 text-xs font-extrabold {{ $filter === $key ? 'bg-[#E5B54F] text-[#08281F]' : 'bg-white/10 text-[#F3D082]' }}"
                    >{{ $label }}</a>
                @endforeach
            </div>

            @guest
                <p class="mb-4 text-xs text-[#A2B5AA]">{{ __('site.moments_login') }}</p>
            @endguest

            @if ($moments->isEmpty())
                <p class="py-16 text-center text-sm text-[#A2B5AA]">{{ __('site.moments_empty') }}</p>
            @else
                <div class="space-y-5">
                    @foreach ($moments as $moment)
                        @php
                            $video = MediaUrl::temporary('r2', $moment->video_url);
                            $poster = MediaUrl::temporary('r2', $moment->poster_url);
                            $width = max(1, (int) ($moment->width ?: 4));
                            $height = max(1, (int) ($moment->height ?: 5));
                            $ratio = max(9 / 16, min(16 / 9, $width / $height));
                            $name = $moment->reciter ? LocaleText::reciterName($moment->reciter) : __('site.footer_brand');
                            $liked = (bool) ($moment->liked ?? false);
                            $saved = (bool) ($moment->saved ?? false);
                        @endphp
                        <article class="overflow-hidden rounded-3xl bg-[#0D3B2E] ring-1 ring-white/10">
                            <div class="flex items-center gap-3 px-4 py-3">
                                <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-[#08281F]">
                                    @if ($moment->reciter && LocaleText::portrait($moment->reciter))
                                        <img src="{{ LocaleText::portrait($moment->reciter) }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <img src="{{ asset('images/logo-mark-light.png') }}" alt="" class="h-6 w-6">
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-extrabold">{{ $name }}</p>
                                    @if ($moment->surah)
                                        <p class="truncate text-xs text-[#F3D082]">{{ LocaleText::surahName($moment->surah) }}</p>
                                    @endif
                                </div>
                            </div>
                            @if ($video)
                                <div class="bg-black" style="aspect-ratio: {{ $ratio }};">
                                    <video
                                        class="h-full w-full object-contain"
                                        src="{{ $video }}"
                                        @if ($poster) poster="{{ $poster }}" @endif
                                        playsinline
                                        controls
                                        x-on:play="$root.querySelectorAll('video').forEach((node) => { if (node !== $el) node.pause() })"
                                    ></video>
                                </div>
                            @endif
                            <div class="space-y-3 px-4 py-3">
                                @if ($moment->title)
                                    <p class="text-sm font-extrabold">{{ $moment->title }}</p>
                                @endif
                                @if ($moment->caption)
                                    <p class="text-sm leading-relaxed text-[#F5F2EA]/90">{{ $moment->caption }}</p>
                                @endif
                                <div class="flex items-center gap-3 text-xs font-extrabold text-[#F3D082]">
                                    @auth
                                        <form method="POST" action="{{ $liked ? route('moments.unlike', $moment) : route('moments.like', $moment) }}">
                                            @csrf
                                            @if ($liked) @method('DELETE') @endif
                                            <button type="submit">{{ $moment->likes_count }} ♥</button>
                                        </form>
                                        <form method="POST" action="{{ $saved ? route('moments.unsave', $moment) : route('moments.save', $moment) }}">
                                            @csrf
                                            @if ($saved) @method('DELETE') @endif
                                            <button type="submit">{{ $saved ? __('site.moments_saved') : __('site.moments_saved') }} {{ $moment->saves_count }}</button>
                                        </form>
                                    @else
                                        <a href="{{ route('login') }}">{{ $moment->likes_count }} ♥</a>
                                        <a href="{{ route('login') }}">{{ $moment->saves_count }}</a>
                                    @endauth
                                    <span>{{ $moment->comments_count }} {{ __('site.moments_comment') }}</span>
                                </div>
                                @auth
                                    <form method="POST" action="{{ route('moments.comment', $moment) }}" class="flex gap-2">
                                        @csrf
                                        <input
                                            type="text"
                                            name="body"
                                            maxlength="500"
                                            required
                                            placeholder="{{ __('site.moments_comment_placeholder') }}"
                                            class="min-w-0 flex-1 rounded-full bg-white/10 px-3 py-2 text-sm outline-none placeholder:text-[#A2B5AA]"
                                        >
                                        <button type="submit" class="rounded-full bg-[#E5B54F] px-3 py-2 text-xs font-extrabold text-[#08281F]">{{ __('site.moments_comment') }}</button>
                                    </form>
                                @endauth
                                @if ($moment->comments->isNotEmpty())
                                    <ul class="space-y-1 text-xs text-[#F5F2EA]/80">
                                        @foreach ($moment->comments->take(3) as $comment)
                                            <li class="flex items-start justify-between gap-2">
                                                <span><strong>{{ $comment->user?->name }}</strong> {{ $comment->body }}</span>
                                                @if (auth()->id() === $comment->user_id)
                                                    <form method="POST" action="{{ route('moments.comments.destroy', [$moment, $comment]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-[#F3D082]">×</button>
                                                    </form>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="mt-6 text-sm">{{ $moments->links() }}</div>
            @endif
        </div>
    </section>
@endsection
