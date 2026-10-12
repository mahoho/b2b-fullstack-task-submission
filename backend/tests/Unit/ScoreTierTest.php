<?php

namespace Tests\Unit;

use App\Enums\ScoreTier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ScoreTierTest extends TestCase
{
    public static function scores(): array
    {
        return [
            'not scored' => [null, 70, ScoreTier::Unscored],
            'top boundary' => [90, 70, ScoreTier::Top],
            'perfect score' => [100, 70, ScoreTier::Top],
            'just below top' => [89, 70, ScoreTier::Eligible],
            'at the threshold' => [70, 70, ScoreTier::Eligible],
            'just below the threshold' => [69, 70, ScoreTier::Below],
            'zero' => [0, 70, ScoreTier::Below],
            'top wins over a higher threshold' => [92, 95, ScoreTier::Top],
        ];
    }

    #[DataProvider('scores')]
    public function test_tier_for_score(?int $score, int $threshold, ScoreTier $expected): void
    {
        $this->assertSame($expected, ScoreTier::for($score, $threshold));
    }
}
