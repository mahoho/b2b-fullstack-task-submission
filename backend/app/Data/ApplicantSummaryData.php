<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ApplicantSummaryData extends Data
{
    /**
     * @param  list<TopTierApplicantData>  $topTier
     */
    public function __construct(
        public int $totalApplicants,
        public int $eligibleApplicants,
        public float $averageAiScore,
        public array $topTier,
    ) {}

    /**
     * @param  list<TopTierApplicantData>  $topTier
     */
    public static function fromAggregates(ApplicantAggregatesData $aggregates, array $topTier): self
    {
        return new self(
            $aggregates->total,
            $aggregates->eligible,
            $aggregates->average === null ? 0.0 : round($aggregates->average, 1),
            $topTier,
        );
    }
}
