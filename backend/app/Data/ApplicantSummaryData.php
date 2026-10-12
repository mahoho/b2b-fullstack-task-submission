<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ApplicantSummaryData extends Data
{
    /**
     * @param list<TopTierApplicantData> $topTier
     */
    public function __construct(
        public int $totalApplicants,
        public int $eligibleApplicants,
        public float $averageAiScore,
        public array $topTier,
    ) {}

    /**
     * @param float|string|null $average AVG() result: null with no scored applicants, a decimal string on MySQL.
     * @param list<TopTierApplicantData> $topTier
     */
    public static function fromAggregates(int $total, int $eligible, float|string|null $average, array $topTier): self
    {
        return new self($total, $eligible, $average === null ? 0.0 : round((float) $average, 1), $topTier);
    }
}
