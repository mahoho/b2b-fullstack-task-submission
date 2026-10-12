<?php
// app/Http/Input/UpdateApplicantStatusData.php

namespace App\Http\Input;

use App\Enums\ApplicantStatus;

class UpdateApplicantStatusData
{
    public function __construct(
        public string $applicantId,
        public string $type,
        public string $id,
        public ApplicantStatus $status,
    ) {}
}
