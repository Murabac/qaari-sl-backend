<?php

namespace Database\Seeders;

use App\Models\Reciter;
use Illuminate\Database\Seeder;

class ReciterSeeder extends Seeder
{
    public function run(): void
    {
        $reciters = [
            [
                'name_somali' => 'Sheekh Cabdiraxmaan Xaaji',
                'name_arabic' => 'الشيخ عبد الرحمن حاجي',
                'name_english' => 'Sheikh Abdirahman Haji',
                'bio_somali' => 'Qaari caan ah oo ka soo jeeda Hargeysa. Wuxuu ku caan baxay qiraatkiisa quruxda badan ee Qur\'aanka Kariimka.',
                'bio_arabic' => 'قارئ مشهور من هرجيسا. يُعرف بتلاوته الجميلة للقرآن الكريم.',
                'bio_english' => 'A well-known reciter from Hargeisa, recognized for his beautiful Quranic recitation.',
                'region' => 'Hargeisa',
            ],
            [
                'name_somali' => 'Sheekh Maxamed Cali',
                'name_arabic' => 'الشيخ محمد علي',
                'name_english' => 'Sheikh Mohamed Ali',
                'bio_somali' => 'Macallin iyo qaari ka tirsan magaalada Burco. Wuxuu dad badan ku baray qiraatka iyo xifdinta.',
                'bio_arabic' => 'معلم وقارئ من مدينة برعو. علّم الكثيرين التلاوة والحفظ.',
                'bio_english' => 'A teacher and reciter from Burao who has taught many students recitation and memorization.',
                'region' => 'Burao',
            ],
            [
                'name_somali' => 'Sheekh Axmed Yuusuf',
                'name_arabic' => 'الشيخ أحمد يوسف',
                'name_english' => 'Sheikh Ahmed Yusuf',
                'bio_somali' => 'Qaari ka soo jeeda Berbera. Codkiisa deggan ayaa dad badan ka dhigay inay jecelaadaan dhegaysiga Qur\'aanka.',
                'bio_arabic' => 'قارئ من بربرة. صوته الهادئ جعل الكثيرين يستمتعون بالاستماع إلى القرآن.',
                'bio_english' => 'A reciter from Berbera whose calm voice has drawn many listeners to the Quran.',
                'region' => 'Berbera',
            ],
            [
                'name_somali' => 'Sheekh Cismaan Cali',
                'name_arabic' => 'الشيخ عثمان علي',
                'name_english' => 'Sheikh Osman Ali',
                'bio_somali' => 'Qaari iyo daaci ka ah Boorama. Wuxuu ku caan yahay qiraatka tartiibka ah ee cad.',
                'bio_arabic' => 'قارئ وداعية من بوراما. يشتهر بتلاوته الهادئة والواضحة.',
                'bio_english' => 'A reciter and preacher from Borama, known for clear and measured recitation.',
                'region' => 'Borama',
            ],
            [
                'name_somali' => 'Mishary Rashid Al-Cafaasi',
                'name_arabic' => 'مشاري بن راشد العفاسي',
                'name_english' => 'Mishary Rashid Alafasy',
                'bio_somali' => 'Qaari reer Kuwait ah, imaamka Masjidka weyn ee Kuwait, caan ku ah qiraatka Qur\'aanka.',
                'bio_arabic' => 'قارئ كويتي وإمام المسجد الكبير في الكويت، معروف بتلاوته للقرآن الكريم.',
                'bio_english' => 'Kuwaiti reciter and imam of the Grand Mosque in Kuwait, known for his Quran recitation.',
                'region' => 'Kuwait',
            ],
        ];

        foreach ($reciters as $reciter) {
            Reciter::query()->updateOrCreate(
                ['name_english' => $reciter['name_english']],
                $reciter,
            );
        }
    }
}
