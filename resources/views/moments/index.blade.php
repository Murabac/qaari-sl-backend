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
            <div class="mb-4 flex items-center justify-between gap-3">
                <h1 class="font-display text-3xl font-bold tracking-tight">{{ LocaleText::nav('Moments', 'site.moments') }}</h1>
                <span class="shrink-0 rounded-full bg-[#D4AF37]/20 px-2.5 py-1 text-[11px] font-bold text-[#E8C882]">Quran Shorts</span>
            </div>

            <form method="GET" action="{{ route('moments.index') }}" class="relative mb-4">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <svg class="pointer-events-none absolute start-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#7A988D]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="{{ __('site.moments_search') }}"
                    class="w-full rounded-2xl border border-white/10 bg-[#0F352A] py-2.5 ps-10 pe-3 text-sm text-[#F5F2EA] outline-none placeholder:text-[#7A988D]"
                >
            </form>

            <div class="mb-5 flex gap-2 overflow-x-auto pb-1">
                @foreach ($filters as $key => $label)
                    <a
                        href="{{ route('moments.index', array_filter(['filter' => $key, 'q' => $search])) }}"
                        class="inline-flex shrink-0 items-center gap-1 rounded-xl border px-3.5 py-1.5 text-xs font-extrabold {{ $filter === $key ? 'border-[#E5B54F] bg-[#E5B54F] text-[#07241C]' : 'border-white/10 bg-[#0A261E] text-[#F5F2EA]' }}"
                    >@if ($filter === $key) <span>✓</span> @endif{{ $label }}</a>
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
                        <article class="overflow-hidden rounded-3xl border border-[#D4AF37]/25 bg-[#092B22] text-white shadow-xl">
                            <div class="px-5 pb-2.5 pt-4">
                                <h2 class="text-base font-extrabold leading-snug">{{ $moment->title ?: $name }}</h2>
                                <p class="text-xs font-medium text-[#E8C882]">
                                    {{ $name }}
                                    @if ($moment->surah)
                                        · {{ LocaleText::surahName($moment->surah) }}
                                    @endif
                                </p>
                            </div>
                            @if ($video)
                                <div class="mx-3 overflow-hidden rounded-2xl bg-black" style="aspect-ratio: {{ $ratio }};">
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
                            <div class="space-y-3 px-5 py-3">
                                <div class="flex items-center justify-between border-b border-white/10 pb-3 text-xs font-semibold">
                                    <div class="flex items-center gap-5">
                                        @auth
                                            <form method="POST" action="{{ $liked ? route('moments.unlike', $moment) : route('moments.like', $moment) }}">
                                                @csrf
                                                @if ($liked) @method('DELETE') @endif
                                                <button type="submit" class="{{ $liked ? 'text-[#E5B54F]' : '' }}">♥ {{ $moment->likes_count }}</button>
                                            </form>
                                            <form method="POST" action="{{ $saved ? route('moments.unsave', $moment) : route('moments.save', $moment) }}">
                                                @csrf
                                                @if ($saved) @method('DELETE') @endif
                                                <button type="submit" class="{{ $saved ? 'text-[#E5B54F]' : '' }}">▣ {{ __('site.moments_saved') }}</button>
                                            </form>
                                        @else
                                            <a href="{{ route('login') }}">♥ {{ $moment->likes_count }}</a>
                                            <a href="{{ route('login') }}">▣ {{ __('site.moments_saved') }}</a>
                                        @endauth
                                        <span>💬 {{ $moment->comments_count }}</span>
                                    </div>
                                    <span class="text-[#E8C882]">♪ {{ __('site.moments_sound') }}</span>
                                </div>
                                @if ($moment->caption)
                                    <p class="text-xs leading-relaxed text-[#D2E4DC]">{{ $moment->caption }}</p>
                                @endif
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
