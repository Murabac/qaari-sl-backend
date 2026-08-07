@extends('layouts.app', ['solidHeader' => true])

@section('title', __('site.account_deletion').' · '.__('site.footer_brand'))

@section('content')
    <section class="bg-qaari-bg px-4 pb-16 pt-28 sm:px-6">
        <div class="mx-auto max-w-xl">
            <p class="text-sm font-semibold text-qaari-accent">{{ __('site.footer_brand') }}</p>
            <h1 class="font-display mt-2 text-3xl font-bold text-qaari-primary">{{ __('site.account_deletion') }}</h1>
            <p class="mt-4 text-sm leading-relaxed text-qaari-muted">
                {{ __('site.account_deletion_intro') }}
            </p>

            <ul class="mt-4 list-disc space-y-1 ps-5 text-sm text-qaari-muted">
                <li>{{ __('site.account_deletion_item_profile') }}</li>
                <li>{{ __('site.account_deletion_item_library') }}</li>
                <li>{{ __('site.account_deletion_item_tokens') }}</li>
            </ul>

            @if ($errors->any())
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('account-deletion.destroy') }}" class="mt-8 space-y-4">
                @csrf
                @method('DELETE')

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-qaari-primary">{{ __('site.email') }}</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        class="w-full rounded-xl border border-qaari-border bg-qaari-card px-4 py-3 text-sm outline-none ring-qaari-accent focus:ring-2"
                    >
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-qaari-primary">{{ __('site.password') }}</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-qaari-border bg-qaari-card px-4 py-3 text-sm outline-none ring-qaari-accent focus:ring-2"
                    >
                </div>

                <label class="flex items-start gap-3 text-sm text-qaari-muted">
                    <input type="checkbox" name="confirm" value="1" required class="mt-1 rounded border-qaari-border">
                    <span>{{ __('site.account_deletion_confirm_check') }}</span>
                </label>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-full bg-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-800"
                >
                    {{ __('site.account_deletion_submit') }}
                </button>
            </form>

            <p class="mt-6 text-sm text-qaari-muted">
                <a href="{{ route('privacy') }}" class="font-semibold text-qaari-primary hover:text-qaari-accent">{{ __('site.privacy_policy') }}</a>
            </p>
        </div>
    </section>
@endsection
