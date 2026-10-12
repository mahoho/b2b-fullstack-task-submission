<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class TopTierApplicantData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public int $aiScore,
    ) {}
}
