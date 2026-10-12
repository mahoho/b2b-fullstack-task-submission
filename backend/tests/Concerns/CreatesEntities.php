<?php

namespace Tests\Concerns;

use App\Entities\Applicant;
use App\Entities\Campaign;
use App\Entities\Partner;
use App\Enums\ApplicantStatus;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Str;

trait CreatesEntities
{
    protected function makePartner(string $token = 'partner-token', string $name = 'Acme University'): Partner
    {
        $partner = new Partner($name, hash('sha256', $token));
        $this->em->persist($partner);
        $this->em->flush();

        return $partner;
    }

    protected function makeCampaign(Partner $partner, int $eligibilityThreshold = 70, string $name = 'Autumn intake'): Campaign
    {
        $campaign = new Campaign($partner, $name, $eligibilityThreshold);
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    /**
     * @param  list<string>  $highlights
     */
    protected function makeApplicant(
        Campaign $campaign,
        ?int $aiScore = 80,
        string $name = 'Applicant',
        string $appliedAt = '2026-10-01 09:00:00',
        array $highlights = ['Strong maths portfolio'],
        ApplicantStatus $status = ApplicantStatus::Pending,
    ): Applicant {
        $applicant = new Applicant(
            $campaign,
            $name,
            Str::slug($name, '.').'@example.com',
            $aiScore,
            $highlights,
            new DateTimeImmutable($appliedAt, new DateTimeZone('UTC')),
        );
        $applicant->setStatus($status);
        $this->em->persist($applicant);
        $this->em->flush();

        return $applicant;
    }
}
