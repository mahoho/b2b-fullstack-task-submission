<?php
// app/Http/Input/ApplicantSummaryQueryData.php

namespace App\Http\Input;

class ApplicantSummaryQueryData
{
    public function __construct(
        public string $campaignId,
        public ?int $minScore = null,
        public int $pageSize = 10,
        public int $pageNumber = 1,
    ) {}
}
