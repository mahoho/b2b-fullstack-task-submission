<?php

namespace App\Data;

use App\Enums\ApplicantStatus;
use App\Enums\ScoreTier;
use Spatie\LaravelData\Data;

class ApplicantAttributesData extends Data
{
    /**
     * @param  list<string>  $highlights
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?int $aiScore,
        public ScoreTier $tier,
        public ApplicantStatus $status,
        public string $appliedAt,
        public array $highlights,
    ) {}
}
