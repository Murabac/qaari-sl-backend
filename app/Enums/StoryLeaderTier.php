<?php

namespace App\Enums;

enum StoryLeaderTier: string
{
    case President = 'president';
    case Minister = 'minister';
    case Board = 'board';

    public function label(): string
    {
        return match ($this) {
            self::President => 'President',
            self::Minister => 'Minister',
            self::Board => 'Board Member',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tier): array => [$tier->value => $tier->label()])
            ->all();
    }
}
