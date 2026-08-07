@extends('layouts.app', ['solidHeader' => true])

@section('title', __('site.login').' · '.__('site.footer_brand'))

@section('content')
    <section class="bg-qaari-bg px-4 pb-16 pt-28 sm:px-6">
        <div class="mx-auto max-w-md">
            <h1 class="font-display text-3xl font-bold text-qaari-primary">{{ __('site.login') }}</h1>
            <p class="mt-2 text-sm text-qaari-muted">{{ __('site.auth_intro') }}</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4">
                @csrf
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-qaari-primary">{{ __('site.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-xl border border-qaari-border bg-qaari-card px-4 py-3 text-sm outline-none ring-qaari-accent focus:ring-2">
                    @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-qaari-primary">{{ __('site.password') }}</label>
                    <input id="password" type="password" name="password" required
                           class="w-full rounded-xl border border-qaari-border bg-qaari-card px-4 py-3 text-sm outline-none ring-qaari-accent focus:ring-2">
                </div>
                <label class="flex items-center gap-2 text-sm text-qaari-muted">
                    <input type="checkbox" name="remember" value="1" class="rounded border-qaari-border text-qaari-primary focus:ring-qaari-accent">
                    {{ __('site.remember_me') }}
                </label>
                <button type="submit" class="w-full rounded-full bg-qaari-primary px-5 py-3 text-sm font-semibold text-qaari-primary-fg transition hover:bg-qaari-soft">
                    {{ __('site.login') }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-qaari-muted">
                {{ __('site.no_account') }}
                <a href="{{ route('register') }}" class="font-semibold text-qaari-primary hover:text-qaari-accent">{{ __('site.register') }}</a>
            </p>
        </div>
    </section>
@endsection
