<?php

namespace App\Http\Requests;

use App\Enums\ApplicantStatus;
use App\Http\Input\ApplicantSummaryQueryData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicantSummaryRequest extends FormRequest
{
    // The partner middleware has authenticated the caller, and route binding only finds the partner's own campaigns.
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'min_score' => ['nullable', 'integer', 'between:0,100'],
            'status' => ['nullable', Rule::enum(ApplicantStatus::class)],
            // TrimStrings and ConvertEmptyStringsToNull already turned a blank search into null.
            'search' => ['nullable', 'string', 'max:100'],
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
        $status = $this->validated('status');
        $search = $this->validated('search');

        return new ApplicantSummaryQueryData(
            minScore: $minScore === null ? null : (int) $minScore,
            status: is_string($status) ? ApplicantStatus::from($status) : null,
            search: is_string($search) ? $search : null,
            pageSize: (int) $this->validated('page.size', 10),
            pageNumber: (int) $this->validated('page.number', 1),
        );
    }
}
