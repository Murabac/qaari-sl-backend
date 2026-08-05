<?php

namespace Database\Seeders;

use App\Enums\StoryLeaderTier;
use App\Models\StoryLeader;
use App\Models\StorySetting;
use Illuminate\Database\Seeder;

class StoryLeaderSeeder extends Seeder
{
    public function run(): void
    {
        StorySetting::current();

        $leaders = [
            [
                'name' => 'Madaxweyne Cabdiraxmaan Maxamed Cabdillaahi Cirro',
                'title' => 'Madaxweynaha Jamhuuriyadda Somaliland',
                'photo_url' => 'images/story/president-irro.png',
                'tier' => StoryLeaderTier::President,
                'sort_order' => 1,
            ],
            [
                'name' => 'Barkhad Jaamac Baatuun',
                'title' => 'Wasiirka Warfaafinta iyo Isgaarsiinta',
                'photo_url' => 'images/story/barkhad.png',
                'tier' => StoryLeaderTier::Minister,
                'sort_order' => 1,
            ],
            [
                'name' => 'Cabdillaahi Sheekh Cali Jowhar',
                'title' => 'Wasiirka Diinta iyo Awqaafta',
                'photo_url' => 'images/story/cabdillaahi.png',
                'tier' => StoryLeaderTier::Minister,
                'sort_order' => 2,
            ],
            [
                'name' => 'Sheekh Berberaawi',
                'title' => 'Xubin Guddiga / Board Member',
                'photo_url' => 'images/story/berberaawi.png',
                'tier' => StoryLeaderTier::Board,
                'sort_order' => 1,
            ],
            [
                'name' => 'Sheekh Dirir',
                'title' => 'Xubin Guddiga / Board Member',
                'photo_url' => 'images/story/dirir.png',
                'tier' => StoryLeaderTier::Board,
                'sort_order' => 2,
            ],
            [
                'name' => 'Sheekh Maxamed Aadan',
                'title' => 'Xubin Guddiga / Board Member',
                'photo_url' => 'images/story/maxamed-aadan.png',
                'tier' => StoryLeaderTier::Board,
                'sort_order' => 3,
            ],
            [
                'name' => 'Sheekh Kaariye',
                'title' => 'Xubin Guddiga / Board Member',
                'photo_url' => 'images/story/kaariye.png',
                'tier' => StoryLeaderTier::Board,
                'sort_order' => 4,
            ],
            [
                'name' => 'Sheekh Xasan Muxumed Cumar (Jaabur)',
                'title' => 'Xubin Guddiga / Board Member',
                'photo_url' => 'images/story/jaabur.png',
                'tier' => StoryLeaderTier::Board,
                'sort_order' => 5,
            ],
        ];

        foreach ($leaders as $leader) {
            StoryLeader::query()->updateOrCreate(
                [
                    'name' => $leader['name'],
                    'tier' => $leader['tier'],
                ],
                [
                    'title' => $leader['title'],
                    'photo_url' => $leader['photo_url'],
                    'sort_order' => $leader['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
