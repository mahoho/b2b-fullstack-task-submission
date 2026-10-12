<?php
// app/Http/Requests/ApplicantSummaryRequest.php

namespace App\Http\Requests;

use App\Http\Input\ApplicantSummaryQueryData;
use Illuminate\Foundation\Http\FormRequest;

class ApplicantSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['campaignId' => $this->route('campaignId')]);
    }

    public function rules(): array
    {
        return [
            'campaignId' => ['required', 'uuid'],
            'min_score' => ['nullable', 'integer', 'between:0,100'],
            'page.size' => ['nullable', 'integer', 'between:1,50'],
            'page.number' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['page.size' => 'page[size]', 'page.number' => 'page[number]'];
    }

    public function toData(): ApplicantSummaryQueryData
    {
        $v = $this->validated();

        return new ApplicantSummaryQueryData(
            campaignId: $v['campaignId'],
            minScore: isset($v['min_score']) ? (int) $v['min_score'] : null,
            pageSize: (int) ($v['page']['size'] ?? 10),
            pageNumber: (int) ($v['page']['number'] ?? 1),
        );
    }
}
