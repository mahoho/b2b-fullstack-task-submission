<?php

namespace App\Enums;

enum ScoreTier: string
{
    case Top = 'top';
    case Eligible = 'eligible';
    case Below = 'below';
    case Unscored = 'unscored';

    public const TOP_SCORE = 90;

    public const TOP_LIMIT = 3;

    public static function for(?int $score, int $eligibilityThreshold): self
    {
        return match (true) {
            $score === null => self::Unscored,
            $score >= self::TOP_SCORE => self::Top,
            $score >= $eligibilityThreshold => self::Eligible,
            default => self::Below,
        };
    }
}
