<?php

namespace App\Filament\Widgets;

use App\Models\Recitation;
use App\Models\Reciter;
use App\Models\Surah;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Library overview';

    protected ?string $description = 'Content available across the Qaari SL platform';

    protected function getStats(): array
    {
        return [
            Stat::make('Reciters', Reciter::query()->count())
                ->description('Profiles in the catalog')
                ->icon('heroicon-o-user-group')
                ->color('primary'),
            Stat::make('Surahs', Surah::query()->count())
                ->description('Reference chapters seeded')
                ->icon('heroicon-o-book-open')
                ->color('success'),
            Stat::make('Recitations', Recitation::query()->count())
                ->description('Audio recordings uploaded')
                ->icon('heroicon-o-musical-note')
                ->color('info'),
        ];
    }
}
