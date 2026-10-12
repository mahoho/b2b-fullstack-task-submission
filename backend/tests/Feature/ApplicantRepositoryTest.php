<?php

namespace Tests\Feature;

use App\Data\ApplicantAggregatesData;
use App\Entities\Applicant;
use Tests\Concerns\CreatesEntities;
use Tests\Concerns\RefreshesDoctrineSchema;
use Tests\TestCase;

class ApplicantRepositoryTest extends TestCase
{
    use CreatesEntities, RefreshesDoctrineSchema;

    public function test_aggregates_come_back_as_a_typed_dto(): void
    {
        $campaign = $this->makeCampaign($this->makePartner(), eligibilityThreshold: 70);
        $this->makeApplicant($campaign, 95);
        $this->makeApplicant($campaign, 64);
        $this->makeApplicant($campaign, null);

        $aggregates = $this->em->getRepository(Applicant::class)->aggregates($campaign);

        $this->assertInstanceOf(ApplicantAggregatesData::class, $aggregates);
        $this->assertSame(3, $aggregates->total);
        $this->assertSame(1, $aggregates->eligible);
        $this->assertSame(79.5, $aggregates->average);
    }

    public function test_aggregates_of_an_empty_campaign_have_no_average(): void
    {
        $campaign = $this->makeCampaign($this->makePartner());

        $aggregates = $this->em->getRepository(Applicant::class)->aggregates($campaign);

        $this->assertSame(0, $aggregates->total);
        $this->assertSame(0, $aggregates->eligible);
        $this->assertNull($aggregates->average);
    }
}
