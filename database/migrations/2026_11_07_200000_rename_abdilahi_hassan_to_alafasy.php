<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reciters')) {
            return;
        }

        $photo = null;
        $portrait = database_path('seed-assets/mishary-rashid-alafasy.jpg');
        if (is_file($portrait)) {
            try {
                $photo = 'reciters/photos/mishary-rashid-alafasy.jpg';
                Storage::disk('r2')->put($photo, file_get_contents($portrait));
            } catch (\Throwable) {
                $photo = null;
            }
        }

        DB::table('reciters')
            ->where('name_english', 'Sheikh Abdilahi Hassan')
            ->update([
                'name_somali' => 'Mishary Rashid Al-Cafaasi',
                'name_arabic' => 'مشاري بن راشد العفاسي',
                'name_english' => 'Mishary Rashid Alafasy',
                'bio_somali' => 'Qaari reer Kuwait ah, imaamka Masjidka weyn ee Kuwait, caan ku ah qiraatka Qur\'aanka.',
                'bio_arabic' => 'قارئ كويتي وإمام المسجد الكبير في الكويت، معروف بتلاوته للقرآن الكريم.',
                'bio_english' => 'Kuwaiti reciter and imam of the Grand Mosque in Kuwait, known for his Quran recitation.',
                'region' => 'Kuwait',
                'photo_url' => $photo,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('reciters')
            ->where('name_english', 'Mishary Rashid Alafasy')
            ->update([
                'name_somali' => 'Sheekh Cabdilaahi Xasan',
                'name_arabic' => 'الشيخ عبد الله حسن',
                'name_english' => 'Sheikh Abdilahi Hassan',
                'bio_somali' => 'Qaari dhalinyaro ah oo ka soo baxay Ceerigaabo. Wuxuu ku dadaalaa inuu Qur\'aanka u gudbiyo jiilka cusub.',
                'bio_arabic' => 'قارئ شاب من عيريجابو. يسعى لنشر القرآن بين الجيل الجديد.',
                'bio_english' => 'A young reciter from Erigavo dedicated to sharing Quranic recitation with a new generation.',
                'region' => 'Erigavo',
                'updated_at' => now(),
            ]);
    }
};
