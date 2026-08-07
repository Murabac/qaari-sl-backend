<?php

namespace App\Services;

use App\Enums\RecitationStatus;
use App\Models\Recitation;
use App\Models\RecitationReviewNote;
use App\Models\User;
use App\Support\AudioMetadata;
use App\Support\VoiceNote;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RecitationReviewService
{
    public function submit(Recitation $recitation): Recitation
    {
        $recitation->update([
            'status' => RecitationStatus::PendingReview,
            'submitted_at' => now(),
        ]);

        return $recitation->fresh(['reciter', 'surah', 'reviewNotes']);
    }

    public function approve(Recitation $recitation, User $reviewer): Recitation
    {
        $recitation->update([
            'status' => RecitationStatus::Approved,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id,
        ]);

        return $recitation->fresh(['reciter', 'surah', 'reviewNotes', 'reviewer']);
    }

    /**
     * @param  array{caption?: string|null, recording?: array<string, mixed>|null}  $options
     */
    public function reject(
        Recitation $recitation,
        User $reviewer,
        ?UploadedFile $voiceNote = null,
        array $options = [],
    ): Recitation {
        $recorded = VoiceNote::storeRecording($options['recording'] ?? null);

        if ($recorded !== null) {
            $path = $recorded['path'];
            $meta = [
                'duration' => $recorded['duration'],
                'file_size' => $recorded['file_size'],
            ];
        } elseif ($voiceNote !== null) {
            $path = $voiceNote->store('reviews/voice-notes', 'r2');
            $meta = AudioMetadata::fromUpload($path, 'r2');
            if (($meta['file_size'] ?? null) === null) {
                $meta['file_size'] = $voiceNote->getSize() ?: null;
            }
        } else {
            throw ValidationException::withMessages([
                'voice_note' => ['Record feedback or upload an audio file before rejecting.'],
            ]);
        }

        RecitationReviewNote::query()->create([
            'recitation_id' => $recitation->id,
            'user_id' => $reviewer->id,
            'audio_url' => $path,
            'duration' => $meta['duration'],
            'file_size' => $meta['file_size'],
            'caption' => $options['caption'] ?? null,
            'status_at_time' => RecitationStatus::Rejected,
        ]);

        $recitation->update([
            'status' => RecitationStatus::Rejected,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id,
        ]);

        return $recitation->fresh(['reciter', 'surah', 'reviewNotes.user', 'reviewer']);
    }

    public function storeAudio(UploadedFile $file, ?string $existingPath = null): array
    {
        if (filled($existingPath)) {
            try {
                Storage::disk('r2')->delete($existingPath);
            } catch (\Throwable) {
                // Ignore missing remote objects.
            }
        }

        $localPath = $file->getRealPath() ?: null;
        $metaFromLocal = filled($localPath) && is_file($localPath)
            ? AudioMetadata::fromUpload($localPath, 'r2')
            : ['duration' => null, 'file_size' => null];

        $path = $file->store('recitations/audio', 'r2');
        $meta = AudioMetadata::fromUpload($path, 'r2');

        return [
            'audio_url' => $path,
            'duration' => $meta['duration'] ?? $metaFromLocal['duration'],
            'file_size' => $meta['file_size'] ?? $metaFromLocal['file_size'] ?? $file->getSize() ?: null,
        ];
    }
}
