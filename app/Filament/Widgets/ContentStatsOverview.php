<?php

namespace App\Filament\Widgets;

use App\Enums\RecitationStatus;
use App\Models\Recitation;
use App\Models\Reciter;
use App\Models\Surah;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Library overview';

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user?->isProduction() && ! $user->isReviewer()) {
            return [
                Stat::make('My reciters', Reciter::query()->where('created_by', $user->id)->count())
                    ->icon('heroicon-o-user-group')
                    ->color('primary'),
                Stat::make('Drafts', Recitation::query()->where('created_by', $user->id)->where('status', RecitationStatus::Draft)->count())
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray'),
                Stat::make('Rejected', Recitation::query()->where('created_by', $user->id)->where('status', RecitationStatus::Rejected)->count())
                    ->icon('heroicon-o-x-circle')
                    ->color('danger'),
                Stat::make('Pending review', Recitation::query()->where('created_by', $user->id)->where('status', RecitationStatus::PendingReview)->count())
                    ->icon('heroicon-o-clock')
                    ->color('warning'),
            ];
        }

        if ($user?->isReviewer()) {
            return [
                Stat::make('Pending reviews', Recitation::query()->where('status', RecitationStatus::PendingReview)->count())
                    ->description('Waiting for approval')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning'),
                Stat::make('Reciters', Reciter::query()->count())
                    ->icon('heroicon-o-user-group')
                    ->color('primary'),
                Stat::make('Approved', Recitation::query()->where('status', RecitationStatus::Approved)->count())
                    ->icon('heroicon-o-check-circle')
                    ->color('success'),
                Stat::make('Surahs', Surah::query()->count())
                    ->icon('heroicon-o-book-open')
                    ->color('info'),
            ];
        }

        return [
            Stat::make('Reciters', Reciter::query()->count())
                ->icon('heroicon-o-user-group')
                ->color('primary'),
            Stat::make('Surahs', Surah::query()->count())
                ->icon('heroicon-o-book-open')
                ->color('success'),
            Stat::make('Recitations', Recitation::query()->count())
                ->icon('heroicon-o-musical-note')
                ->color('info'),
        ];
    }
}
