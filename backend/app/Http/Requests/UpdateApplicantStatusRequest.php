// app/Http/Requests/UpdateApplicantStatusRequest.php
<?php

namespace App\Http\Requests;

use App\Entities\Applicant;
use App\Enums\ApplicantStatus;
use App\Http\Input\UpdateApplicantStatusData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantStatusRequest extends FormRequest
{
    // The partner middleware has already authenticated the caller and route binding has checked ownership.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Applicant $applicant */
        $applicant = $this->route('applicant');

        return [
            'data.type' => ['required', 'in:applicants'],
            'data.id' => [
                'required',
                fn (string $attribute, mixed $value, \Closure $fail) => strtolower((string) $value) === $applicant->getId()
                    || $fail('The data.id field must match the applicant id in the URL.'),
            ],
            'data.attributes.status' => ['required', Rule::enum(ApplicantStatus::class)],
        ];
    }

    public function attributes(): array
    {
        return ['data.attributes.status' => 'status'];
    }

    public function toData(): UpdateApplicantStatusData
    {
        return new UpdateApplicantStatusData(
            status: ApplicantStatus::from($this->validated('data.attributes.status')),
        );
    }
}
