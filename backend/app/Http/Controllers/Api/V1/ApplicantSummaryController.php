<?php
// app/Http/Controllers/Api/V1/ApplicantSummaryController.php

namespace App\Http\Controllers\Api\V1;

use App\Data\ApplicantSummaryDocumentData;
use App\Http\Requests\ApplicantSummaryRequest;
use App\Services\ApplicantSummaryService;

class ApplicantSummaryController
{
    public function __invoke(ApplicantSummaryRequest $request, ApplicantSummaryService $service): ApplicantSummaryDocumentData
    {
        return $service->summarize($request->attributes->get('partner'), $request->toData(), $request->url());
    }
}
