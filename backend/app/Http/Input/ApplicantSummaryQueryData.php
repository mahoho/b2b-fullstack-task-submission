<?php

namespace App\Http\Input;

use App\Enums\ApplicantStatus;
use Spatie\LaravelData\Data;

class ApplicantSummaryQueryData extends Data
{
    public function __construct(
        public ?int $minScore = null,
        public ?ApplicantStatus $status = null,
        public ?string $search = null,
        public int $pageSize = 10,
        public int $pageNumber = 1,
    ) {}
}
