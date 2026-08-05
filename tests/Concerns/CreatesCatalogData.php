<?php

namespace Tests\Concerns;

use App\Enums\RecitationStatus;
use App\Models\Recitation;
use App\Models\Reciter;
use App\Models\Surah;
use App\Models\User;

trait CreatesCatalogData
{
    protected function makeUser(array $overrides = []): User
    {
        return User::query()->create(array_merge([
            'name' => 'Listener',
            'email' => 'listener@example.com',
            'password' => 'password',
        ], $overrides));
    }

    protected function makeSurah(array $overrides = []): Surah
    {
        static $number = 1;

        $n = $overrides['number'] ?? $number++;

        return Surah::query()->create(array_merge([
            'number' => $n,
            'name_arabic' => 'سورة',
            'name_somali' => 'Suurad',
            'name_english' => 'Surah '.$n,
            'verse_count' => 7,
        ], $overrides));
    }

    protected function makeReciter(array $overrides = []): Reciter
    {
        return Reciter::query()->create(array_merge([
            'name_somali' => 'Qaari',
            'name_arabic' => 'قارئ',
            'name_english' => 'Test Reciter',
            'bio_english' => 'A test reciter',
            'region' => 'Hargeisa',
        ], $overrides));
    }

    protected function makeRecitation(
        ?Reciter $reciter = null,
        ?Surah $surah = null,
        RecitationStatus $status = RecitationStatus::Approved,
        array $overrides = [],
    ): Recitation {
        $reciter ??= $this->makeReciter();
        $surah ??= $this->makeSurah();

        return Recitation::query()->create(array_merge([
            'reciter_id' => $reciter->id,
            'surah_id' => $surah->id,
            'audio_url' => 'recitations/test.mp3',
            'duration' => 120,
            'file_size' => 1024,
            'status' => $status,
        ], $overrides));
    }
}
