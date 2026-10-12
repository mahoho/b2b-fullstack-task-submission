<?php

namespace Tests\Feature;

use App\Entities\Applicant;
use App\Enums\ApplicantStatus;
use Doctrine\ORM\Tools\SchemaValidator;
use Tests\Concerns\CreatesEntities;
use Tests\Concerns\RefreshesDoctrineSchema;
use Tests\TestCase;

class DoctrineMappingTest extends TestCase
{
    use CreatesEntities, RefreshesDoctrineSchema;

    public function test_mapping_is_valid(): void
    {
        $this->assertSame([], (new SchemaValidator($this->em))->validateMapping());
    }

    public function test_applicant_round_trips_through_the_database(): void
    {
        $campaign = $this->makeCampaign($this->makePartner());
        $applicant = $this->makeApplicant($campaign, 88, 'Ada Lovelace', '2026-10-01 09:30:00', ['Strong maths portfolio', 'Led a robotics club']);
        $id = $applicant->getId();
        $this->em->clear();

        $reloaded = $this->em->find(Applicant::class, $id);

        $this->assertInstanceOf(Applicant::class, $reloaded);
        $this->assertSame('Ada Lovelace', $reloaded->getFullName());
        $this->assertSame('ada.lovelace@example.com', $reloaded->getEmail());
        $this->assertSame(88, $reloaded->getAiScore());
        $this->assertSame(['Strong maths portfolio', 'Led a robotics club'], $reloaded->getHighlights());
        $this->assertSame(ApplicantStatus::Pending, $reloaded->getStatus());
        $this->assertSame('2026-10-01T09:30:00+00:00', $reloaded->getAppliedAt()->format(DATE_ATOM));
        $this->assertSame($campaign->getId(), $reloaded->getCampaign()->getId());
        $this->assertSame(70, $reloaded->getCampaign()->getEligibilityThreshold());
    }

    public function test_unscored_applicant_keeps_a_null_score(): void
    {
        $id = $this->makeApplicant($this->makeCampaign($this->makePartner()), null)->getId();
        $this->em->clear();

        $reloaded = $this->em->find(Applicant::class, $id);
        $this->assertInstanceOf(Applicant::class, $reloaded);
        $this->assertNull($reloaded->getAiScore());
    }
}
