@php
    $leader = $item['leader'];
    $photo = $item['photo_url'];
    $sizes = [
        'lg' => ['photo' => 'h-36 w-36 sm:h-44 sm:w-44', 'name' => 'text-2xl sm:text-3xl', 'pad' => 'p-8', 'max' => 'max-w-md'],
        'md' => ['photo' => 'h-28 w-28 sm:h-32 sm:w-32', 'name' => 'text-xl', 'pad' => 'p-6', 'max' => 'max-w-sm'],
        'board' => ['photo' => 'h-28 w-28', 'name' => 'text-lg', 'pad' => 'px-6 pb-7 pt-8', 'max' => ''],
        'sm' => ['photo' => 'h-20 w-20', 'name' => 'text-base', 'pad' => 'p-5', 'max' => ''],
    ];
    $s = $sizes[$size] ?? $sizes['sm'];
    $isBoard = $size === 'board';
@endphp

<article class="relative h-full w-full {{ $s['max'] }} overflow-hidden rounded-2xl bg-qaari-card {{ $s['pad'] }} text-center ring-1 ring-qaari-accent/35 shadow-[0_8px_24px_rgba(27,58,46,0.06)]">
    @if ($isBoard)
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-transparent via-qaari-accent/70 to-transparent"></div>
        <div class="qaari-pattern-gold absolute inset-0 opacity-30"></div>
    @endif

    <div class="relative mx-auto mb-5 overflow-hidden rounded-full bg-qaari-primary/10 ring-2 {{ $isBoard ? 'ring-qaari-accent/40 ring-offset-4 ring-offset-qaari-card' : 'ring-qaari-border' }} {{ $s['photo'] }}">
        @if ($photo)
            <img src="{{ $photo }}" alt="{{ $leader->name }}" class="h-full w-full object-cover">
        @else
            <div class="flex h-full items-center justify-center font-display text-2xl font-semibold text-qaari-primary">
                {{ mb_substr($leader->name, 0, 1) }}
            </div>
        @endif
    </div>

    <div class="relative">
        <h3 class="font-display font-semibold leading-snug text-qaari-primary {{ $s['name'] }}">{{ $leader->name }}</h3>
        <p class="mt-2 text-sm leading-relaxed text-qaari-muted">{{ $leader->title }}</p>
    </div>
</article>
