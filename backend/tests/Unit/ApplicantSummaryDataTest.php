<?php

namespace Tests\Unit;

use App\Data\ApplicantAggregatesData;
use App\Data\ApplicantSummaryData;
use App\Data\TopTierApplicantData;
use Tests\TestCase;

class ApplicantSummaryDataTest extends TestCase
{
    public function test_no_scored_applicants_gives_a_zero_average(): void
    {
        $summary = ApplicantSummaryData::fromAggregates(new ApplicantAggregatesData(total: 0, eligible: 0, average: null), []);

        $this->assertSame(0.0, $summary->averageAiScore);
    }

    public function test_average_is_rounded_to_one_decimal(): void
    {
        $this->assertSame(71.7, ApplicantSummaryData::fromAggregates(new ApplicantAggregatesData(3, 2, 71.666666), [])->averageAiScore);
        $this->assertSame(76.4, ApplicantSummaryData::fromAggregates(new ApplicantAggregatesData(9, 6, 76.375), [])->averageAiScore);
    }

    public function test_aggregates_from_a_mysql_row_are_normalised(): void
    {
        // MySQL returns COUNT and SUM as strings and AVG as a decimal string.
        $aggregates = ApplicantAggregatesData::fromRow(['total' => '9', 'eligible' => '6', 'average' => '76.3750']);

        $this->assertSame(9, $aggregates->total);
        $this->assertSame(6, $aggregates->eligible);
        $this->assertSame(76.375, $aggregates->average);
    }

    public function test_counts_and_top_tier_pass_through(): void
    {
        $top = [new TopTierApplicantData('id-1', 'Ada Lovelace', 95)];
        $summary = ApplicantSummaryData::fromAggregates(new ApplicantAggregatesData(9, 6, 76.375), $top);

        $this->assertSame(9, $summary->totalApplicants);
        $this->assertSame(6, $summary->eligibleApplicants);
        $this->assertSame($top, $summary->topTier);
    }
}
