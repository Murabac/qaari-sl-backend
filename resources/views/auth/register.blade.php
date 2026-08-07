@extends('layouts.app', ['solidHeader' => true])

@section('title', __('site.register').' · '.__('site.footer_brand'))

@section('content')
    <section class="bg-qaari-bg px-4 pb-16 pt-28 sm:px-6">
        <div class="mx-auto max-w-md">
            <h1 class="font-display text-3xl font-bold text-qaari-primary">{{ __('site.register') }}</h1>
            <p class="mt-2 text-sm text-qaari-muted">{{ __('site.auth_intro') }}</p>

            <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4">
                @csrf
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-semibold text-qaari-primary">{{ __('site.name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                           class="w-full rounded-xl border border-qaari-border bg-qaari-card px-4 py-3 text-sm outline-none ring-qaari-accent focus:ring-2">
                    @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-qaari-primary">{{ __('site.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                           class="w-full rounded-xl border border-qaari-border bg-qaari-card px-4 py-3 text-sm outline-none ring-qaari-accent focus:ring-2">
                    @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-qaari-primary">{{ __('site.password') }}</label>
                    <input id="password" type="password" name="password" required
                           class="w-full rounded-xl border border-qaari-border bg-qaari-card px-4 py-3 text-sm outline-none ring-qaari-accent focus:ring-2">
                    @error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-qaari-primary">{{ __('site.password_confirm') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                           class="w-full rounded-xl border border-qaari-border bg-qaari-card px-4 py-3 text-sm outline-none ring-qaari-accent focus:ring-2">
                </div>
                <button type="submit" class="w-full rounded-full bg-qaari-primary px-5 py-3 text-sm font-semibold text-qaari-primary-fg transition hover:bg-qaari-soft">
                    {{ __('site.create_account') }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-qaari-muted">
                {{ __('site.has_account') }}
                <a href="{{ route('login') }}" class="font-semibold text-qaari-primary hover:text-qaari-accent">{{ __('site.login') }}</a>
            </p>
        </div>
    </section>
@endsection
