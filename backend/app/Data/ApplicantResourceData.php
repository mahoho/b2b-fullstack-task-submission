<?php

namespace App\Data;

use App\Entities\Applicant;
use App\Enums\ScoreTier;
use Spatie\LaravelData\Data;

class ApplicantResourceData extends Data
{
    public function __construct(
        public string $type,
        public string $id,
        public ApplicantAttributesData $attributes,
    ) {}

    // The threshold is passed in so building a page of resources never walks the lazy campaign association.
    public static function fromEntity(Applicant $applicant, int $eligibilityThreshold): self
    {
        return new self('applicants', $applicant->getId(), new ApplicantAttributesData(
            name: $applicant->getFullName(),
            email: $applicant->getEmail(),
            aiScore: $applicant->getAiScore(),
            tier: ScoreTier::for($applicant->getAiScore(), $eligibilityThreshold),
            status: $applicant->getStatus(),
            appliedAt: $applicant->getAppliedAt()->format(DATE_ATOM),
            highlights: $applicant->getHighlights(),
        ));
    }
}
