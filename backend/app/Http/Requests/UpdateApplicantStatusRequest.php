<?php

namespace App\Http\Requests;

use App\Entities\Applicant;
use App\Enums\ApplicantStatus;
use App\Http\Input\UpdateApplicantStatusData;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantStatusRequest extends FormRequest
{
    // The partner middleware has authenticated the caller, and route binding only finds the partner's own applicants.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data.type' => ['required', 'in:applicants'],
            // bail: the comparison below only runs on a string, so an array here is a 422 rather than a PHP error.
            'data.id' => ['bail', 'required', 'string', function (string $attribute, string $value, Closure $fail): void {
                if (strtolower($value) !== $this->applicant()->getId()) {
                    $fail('The data.id field must match the applicant in the URL.');
                }
            }],
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

    private function applicant(): Applicant
    {
        return $this->route('applicant');
    }
}
