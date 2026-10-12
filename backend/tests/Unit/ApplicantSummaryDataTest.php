<?php

namespace Tests\Unit;

use App\Data\ApplicantSummaryData;
use App\Data\TopTierApplicantData;
use Tests\TestCase;

class ApplicantSummaryDataTest extends TestCase
{
    public function test_no_scored_applicants_gives_a_zero_average(): void
    {
        $this->assertSame(0.0, ApplicantSummaryData::fromAggregates(0, 0, null, [])->averageAiScore);
    }

    public function test_average_is_rounded_to_one_decimal(): void
    {
        $this->assertSame(71.7, ApplicantSummaryData::fromAggregates(3, 2, 71.666666, [])->averageAiScore);
        $this->assertSame(76.4, ApplicantSummaryData::fromAggregates(9, 6, 76.375, [])->averageAiScore);
    }

    public function test_mysql_decimal_strings_are_accepted(): void
    {
        $this->assertSame(76.4, ApplicantSummaryData::fromAggregates(9, 6, '76.3750', [])->averageAiScore);
    }

    public function test_counts_and_top_tier_pass_through(): void
    {
        $top = [new TopTierApplicantData('id-1', 'Ada Lovelace', 95)];
        $summary = ApplicantSummaryData::fromAggregates(9, 6, 76.375, $top);

        $this->assertSame(9, $summary->totalApplicants);
        $this->assertSame(6, $summary->eligibleApplicants);
        $this->assertSame($top, $summary->topTier);
    }
}
