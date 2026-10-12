<?php

namespace App\Http\Input;

use Spatie\LaravelData\Data;

class ApplicantSummaryQueryData extends Data
{
    public function __construct(
        public ?int $minScore = null,
        public int $pageSize = 10,
        public int $pageNumber = 1,
    ) {}
}
