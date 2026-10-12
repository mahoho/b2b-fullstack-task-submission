<?php

namespace App\Http\Requests;

use App\Enums\ApplicantStatus;
use App\Http\Input\UpdateApplicantStatusData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantStatusRequest extends FormRequest
{
    // The partner middleware has already authenticated the caller; ownership is checked in the controller.
    public function authorize(): bool
    {
        return true;
    }

    // Validates the route id together with the body, without writing it into the body itself.
    public function validationData(): array
    {
        return [...$this->all(), 'applicantId' => $this->route('applicantId')];
    }

    public function rules(): array
    {
        return [
            'applicantId' => ['required', 'uuid'],
            'data.type' => ['required', 'in:applicants'],
            'data.id' => ['required', 'same:applicantId'],
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
            applicantId: $this->validated('applicantId'),
            type: $this->validated('data.type'),
            id: $this->validated('data.id'),
            status: ApplicantStatus::from($this->validated('data.attributes.status')),
        );
    }
}
