<section class="bg-qaari-bg px-4 py-10 sm:px-6 sm:py-16" aria-labelledby="apk-band-title">
    <div class="relative mx-auto max-w-7xl overflow-hidden rounded-3xl bg-qaari-deep text-qaari-primary-fg">
        <div class="qaari-pattern-gold pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="relative flex flex-col gap-8 px-6 py-10 sm:px-12 sm:py-14 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-5 sm:items-center">
                <img
                    src="{{ asset('images/logo-mark-light.png') }}"
                    alt=""
                    class="h-16 w-16 shrink-0 sm:h-20 sm:w-20"
                    width="80"
                    height="80"
                >
                <div class="min-w-0">
                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-qaari-accent">
                        {{ __('site.download_band_eyebrow') }}
                    </p>
                    <h2 id="apk-band-title" class="font-display text-3xl font-bold leading-tight sm:text-4xl">
                        {{ __('site.download_band_title') }}
                    </h2>
                    <p class="mt-3 max-w-xl text-sm leading-relaxed text-qaari-primary-fg/75 sm:text-base">
                        {{ __('site.download_band_desc') }}
                    </p>
                </div>
            </div>
            <div class="shrink-0">
                @include('partials.apk-download')
            </div>
        </div>
    </div>
</section>
