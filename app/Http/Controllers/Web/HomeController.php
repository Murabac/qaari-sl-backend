<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Recitation;
use App\Models\Reciter;
use App\Models\StorySetting;
use App\Support\MediaUrl;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured = Reciter::query()
            ->withApprovedRecitations()
            ->withCount('approvedRecitations')
            ->orderBy('name_english')
            ->limit(6)
            ->get();

        $stats = [
            'reciters' => Reciter::query()->withApprovedRecitations()->count(),
            'recitations' => Recitation::query()->approved()->count(),
        ];

        $listenNow = null;
        $first = $featured->first();

        if ($first) {
            $recitation = $first->approvedRecitations()
                ->with('surah')
                ->orderBy('id')
                ->first();

            if ($recitation) {
                $listenNow = [
                    'reciter' => $first,
                    'recitation' => $recitation,
                    'audio_url' => MediaUrl::temporary('r2', $recitation->audio_url),
                ];
            }
        }

        $settings = StorySetting::current();
        $showPartners = $settings->partners_section_enabled;
        $partners = collect();

        if ($showPartners) {
            $partners = Partner::query()
                ->active()
                ->ordered()
                ->get()
                ->map(fn (Partner $partner) => [
                    'partner' => $partner,
                    'logo_url' => MediaUrl::temporary('r2', $partner->logo_url),
                ]);
        }

        return view('home', [
            'featured' => $featured,
            'stats' => $stats,
            'listenNow' => $listenNow,
            'showPartners' => $showPartners,
            'partners' => $partners,
        ]);
    }
}
