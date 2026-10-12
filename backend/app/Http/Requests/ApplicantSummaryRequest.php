// app/Http/Requests/ApplicantSummaryRequest.php
<?php

namespace App\Http\Requests;

use App\Http\Input\ApplicantSummaryQueryData;
use Illuminate\Foundation\Http\FormRequest;

class ApplicantSummaryRequest extends FormRequest
{
    // The partner middleware has already authenticated the caller and route binding has checked ownership.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
        $minScore = $this->validated('min_score');

        return new ApplicantSummaryQueryData(
            minScore: $minScore === null ? null : (int) $minScore,
            pageSize: (int) $this->validated('page.size', 10),
            pageNumber: (int) $this->validated('page.number', 1),
        );
    }
}
