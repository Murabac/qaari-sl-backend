<?php

namespace Database\Seeders;

use App\Models\Ayah;
use App\Models\Surah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AyahSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/ayahs-uthmani.json');

        if (! is_file($path)) {
            $this->command?->warn('Ayah dataset missing at database/data/ayahs-uthmani.json — skipping.');

            return;
        }

        /** @var list<array{surah:int,ayah:int,text:string}> $rows */
        $rows = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $surahIds = Surah::query()->pluck('id', 'number');

        Ayah::query()->delete();

        $buffer = [];
        $now = now();

        foreach ($rows as $row) {
            $surahId = $surahIds[(int) $row['surah']] ?? null;

            if (! $surahId) {
                continue;
            }

            $buffer[] = [
                'surah_id' => $surahId,
                'number' => (int) $row['ayah'],
                'text_uthmani' => $row['text'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($buffer) >= 500) {
                DB::table('ayahs')->insert($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            DB::table('ayahs')->insert($buffer);
        }
    }
}
