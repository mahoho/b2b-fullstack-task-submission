<?php

namespace App\Http\Input;

use App\Enums\ApplicantStatus;
use Spatie\LaravelData\Data;

class UpdateApplicantStatusData extends Data
{
    public function __construct(
        public ApplicantStatus $status,
    ) {}
}
