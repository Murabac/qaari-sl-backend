@extends('layouts.app', ['solidHeader' => true])

@php
    use App\Support\LocaleText;
    $locale = LocaleText::locale();
@endphp

@section('title', __('site.settings').' · '.__('site.footer_brand'))

@section('content')
    <section class="qaari-app-page bg-qaari-bg">
        <header class="qaari-forest-header">
            <h1 class="qaari-forest-header__title">{{ __('site.settings') }}</h1>
        </header>

        <div class="mx-auto max-w-lg space-y-6 px-4 py-6 sm:px-6">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-qaari-muted">{{ __('site.language') }}</p>
                <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-qaari-border">
                    @foreach ([
                        'en' => ['English', 'English'],
                        'so' => ['Somali', 'Soomaali'],
                        'ar' => ['Arabic', 'العربية'],
                    ] as $code => [$label, $native])
                        <a
                            href="{{ route('locale.switch', $code) }}"
                            class="flex items-center justify-between gap-3 border-b border-qaari-border px-4 py-3.5 last:border-b-0 {{ $locale === $code ? 'bg-qaari-primary/5' : '' }}"
                        >
                            <div>
                                <p class="text-sm font-bold text-qaari-primary">{{ $label }}</p>
                                <p class="text-xs text-qaari-muted {{ $code === 'ar' ? 'font-arabic' : '' }}" @if($code === 'ar') dir="rtl" @endif>{{ $native }}</p>
                            </div>
                            @if ($locale === $code)
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-qaari-primary text-qaari-accent">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-qaari-muted">{{ __('site.account') }}</p>
                <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-qaari-border">
                    @auth
                        <div class="border-b border-qaari-border px-4 py-3.5">
                            <p class="text-sm font-bold text-qaari-primary">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-qaari-muted">{{ auth()->user()->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full px-4 py-3.5 text-start text-sm font-bold text-qaari-primary hover:bg-qaari-bg">
                                {{ __('site.logout') }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="block border-b border-qaari-border px-4 py-3.5 text-sm font-bold text-qaari-primary hover:bg-qaari-bg">
                            {{ __('site.login') }}
                        </a>
                        <a href="{{ route('register') }}" class="block px-4 py-3.5 text-sm font-bold text-qaari-primary hover:bg-qaari-bg">
                            {{ __('site.register') }}
                        </a>
                    @endauth
                </div>
            </div>

            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-qaari-muted">{{ __('site.about') }}</p>
                <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-qaari-border">
                    <a href="{{ route('story') }}" class="block border-b border-qaari-border px-4 py-3.5 text-sm font-bold text-qaari-primary hover:bg-qaari-bg">
                        {{ __('site.story') }}
                    </a>
                    <a href="{{ route('privacy') }}" class="block border-b border-qaari-border px-4 py-3.5 text-sm font-bold text-qaari-primary hover:bg-qaari-bg">
                        {{ __('site.privacy_policy') }}
                    </a>
                    <a href="{{ route('account-deletion') }}" class="block px-4 py-3.5 text-sm font-bold text-qaari-primary hover:bg-qaari-bg">
                        {{ __('site.account_deletion') }}
                    </a>
                </div>
            </div>

            <p class="text-center text-xs text-qaari-muted">{{ __('site.footer_brand') }}</p>
        </div>
    </section>
@endsection
