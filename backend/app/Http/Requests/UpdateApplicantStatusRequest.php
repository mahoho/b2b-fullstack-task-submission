<?php
// app/Http/Requests/UpdateApplicantStatusRequest.php

namespace App\Http\Requests;

use App\Enums\ApplicantStatus;
use App\Http\Input\UpdateApplicantStatusData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['applicantId' => $this->route('applicantId')]);
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
        $v = $this->validated();

        return new UpdateApplicantStatusData(
            applicantId: $v['applicantId'],
            type: $v['data']['type'],
            id: $v['data']['id'],
            status: ApplicantStatus::from($v['data']['attributes']['status']),
        );
    }
}
