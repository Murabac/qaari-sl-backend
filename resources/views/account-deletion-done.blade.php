@extends('layouts.app', ['solidHeader' => true])

@section('title', __('site.account_deletion_done').' · '.__('site.footer_brand'))

@section('content')
    <section class="bg-qaari-bg px-4 pb-16 pt-28 sm:px-6">
        <div class="mx-auto max-w-xl text-center">
            <h1 class="font-display text-3xl font-bold text-qaari-primary">{{ __('site.account_deletion_done') }}</h1>
            <p class="mt-4 text-sm leading-relaxed text-qaari-muted">
                {{ __('site.account_deletion_done_body') }}
            </p>
            <a
                href="{{ route('home') }}"
                class="mt-8 inline-flex rounded-full bg-qaari-primary px-5 py-3 text-sm font-semibold text-qaari-primary-fg"
            >{{ __('site.home') }}</a>
        </div>
    </section>
@endsection
