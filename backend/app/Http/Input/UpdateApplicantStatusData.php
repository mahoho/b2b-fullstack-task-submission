<?php

namespace App\Http\Input;

use App\Enums\ApplicantStatus;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Same;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class UpdateApplicantStatusData extends Data
{
    public function __construct(
        #[FromRouteParameter('applicantId'), Uuid]
        public string $applicantId,
        #[MapInputName('data.type'), In('applicants')]
        public string $type,
        #[MapInputName('data.id'), Same('applicantId')]
        public string $id,
        #[MapInputName('data.attributes.status')]
        public ApplicantStatus $status,
    ) {}

    public static function attributes(): array
    {
        return ['data.attributes.status' => 'status'];
    }
}
