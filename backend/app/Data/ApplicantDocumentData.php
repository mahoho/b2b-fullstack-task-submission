<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ApplicantDocumentData extends Data
{
    public function __construct(
        public ApplicantResourceData $data,
    ) {}
}
