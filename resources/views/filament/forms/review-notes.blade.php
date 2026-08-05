<div class="space-y-3">
    @forelse ($notes as $note)
        <div class="rounded-xl border border-[rgba(27,58,46,0.12)] bg-[#f7f4ee] p-3">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm">
                <span class="font-semibold text-[#1b3a2e]">{{ $note->user?->name ?? 'Reviewer' }}</span>
                <span class="text-[#6b6252]">{{ $note->created_at?->diffForHumans() }}</span>
            </div>
            @if (filled($note->caption))
                <p class="mb-2 text-sm text-[#1a2416]">{{ $note->caption }}</p>
            @endif
            @php
                $url = \App\Support\MediaUrl::temporary('r2', $note->audio_url);
            @endphp
            @if ($url)
                <audio controls preload="none" class="w-full" src="{{ $url }}"></audio>
            @else
                <p class="text-xs text-[#c0392b]">Voice note unavailable</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-[#6b6252]">No voice notes yet.</p>
    @endforelse
</div>
