@extends('layouts.app', ['solidHeader' => true])

@section('title', __('site.privacy_policy').' · '.__('site.footer_brand'))

@section('content')
    <section class="bg-qaari-bg px-4 pb-16 pt-28 sm:px-6">
        <article class="prose prose-neutral mx-auto max-w-3xl">
            <p class="text-sm font-semibold text-qaari-accent">{{ __('site.footer_brand') }}</p>
            <h1 class="font-display mt-2 text-3xl font-bold text-qaari-primary sm:text-4xl">{{ __('site.privacy_policy') }}</h1>
            <p class="mt-2 text-sm text-qaari-muted">{{ __('site.privacy_last_updated') }}: 8 August 2026</p>

            <div class="mt-10 space-y-8 text-base leading-relaxed text-qaari-muted">
                <section>
                    <h2 class="font-display text-xl font-bold text-qaari-primary">1. Who we are</h2>
                    <p class="mt-3">
                        Xulka Quraa'da (“we”, “us”) provides a website and mobile application for listening to Quranic
                        recitations from the Somali community. This Privacy Policy explains what information we collect,
                        how we use it, and the choices you have. The service is available at
                        <a href="https://qaari.mahaysaa.com" class="font-semibold text-qaari-primary hover:text-qaari-accent">https://qaari.mahaysaa.com</a>
                        and in the Xulka Quraada Android app.
                    </p>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-qaari-primary">2. Information we collect</h2>
                    <ul class="mt-3 list-disc space-y-2 ps-5">
                        <li>
                            <span class="font-semibold text-qaari-primary">Account information:</span>
                            if you register, we collect your name, email address, and a hashed password.
                        </li>
                        <li>
                            <span class="font-semibold text-qaari-primary">Library data:</span>
                            favorites and playlists you create while signed in.
                        </li>
                        <li>
                            <span class="font-semibold text-qaari-primary">Usage data:</span>
                            basic technical logs needed to run the service (for example API requests, timestamps, and
                            approximate device/app identifiers used by the operating system or hosting provider).
                        </li>
                        <li>
                            <span class="font-semibold text-qaari-primary">Audio playback:</span>
                            when you play a recitation, the app requests audio from our servers / storage. We do not
                            sell listening history.
                        </li>
                    </ul>
                    <p class="mt-3">
                        We do not knowingly collect sensitive personal data such as payment card numbers, precise GPS
                        location, contacts, SMS, or microphone recordings in the public listener app.
                    </p>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-qaari-primary">3. How we use information</h2>
                    <ul class="mt-3 list-disc space-y-2 ps-5">
                        <li>Provide account login, favorites, and playlists</li>
                        <li>Stream and display Quranic recitations and related content</li>
                        <li>Secure the service, prevent abuse, and fix errors</li>
                        <li>Communicate about account or service issues when needed</li>
                    </ul>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-qaari-primary">4. Sharing of information</h2>
                    <p class="mt-3">
                        We do not sell your personal information. We may share data with service providers that help us
                        host the website, database, and media files (for example hosting and object storage providers),
                        only as needed to operate the service. We may disclose information if required by law or to
                        protect the rights, safety, or integrity of the service and its users.
                    </p>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-qaari-primary">5. Data retention</h2>
                    <p class="mt-3">
                        Account, favorites, and playlist data are kept while your account remains active. You may
                        request deletion of your account and associated personal data by contacting us (see below).
                        Technical logs are retained only as long as reasonably needed for security and operations.
                    </p>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-qaari-primary">6. Security</h2>
                    <p class="mt-3">
                        We use industry-standard measures such as HTTPS and hashed passwords. No method of transmission
                        or storage is completely secure, so we cannot guarantee absolute security.
                    </p>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-qaari-primary">7. Children’s privacy</h2>
                    <p class="mt-3">
                        The service is intended for a general audience interested in Quranic recitation. It is not
                        directed at children under 13. If you believe a child has provided personal information, contact
                        us and we will take appropriate steps to remove it.
                    </p>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-qaari-primary">8. Your choices</h2>
                    <ul class="mt-3 list-disc space-y-2 ps-5">
                        <li>You can use much of the catalog without creating an account</li>
                        <li>You can update or remove favorites and playlists while signed in</li>
                        <li>You can request account deletion by contacting us</li>
                        <li>You can uninstall the mobile app at any time</li>
                    </ul>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-qaari-primary">9. Changes to this policy</h2>
                    <p class="mt-3">
                        We may update this Privacy Policy from time to time. The “Last updated” date at the top will
                        change when we do. Continued use of the service after an update means you accept the revised
                        policy.
                    </p>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-qaari-primary">10. Contact</h2>
                    <p class="mt-3">
                        For privacy questions or account deletion requests, contact us at
                        <a href="mailto:admin@qaarisl.com" class="font-semibold text-qaari-primary hover:text-qaari-accent">admin@qaarisl.com</a>
                        or through the Xulka Quraa'da website.
                    </p>
                </section>
            </div>
        </article>
    </section>
@endsection
