<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ApplicantSummaryMetaData extends Data
{
    public function __construct(
        public ApplicantSummaryData $summary,
        public PageMetaData $page,
    ) {}
}
