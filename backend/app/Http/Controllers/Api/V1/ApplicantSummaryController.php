<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\ApplicantSummaryDocumentData;
use App\Http\Input\ApplicantSummaryQueryData;
use App\Services\ApplicantSummaryService;
use Illuminate\Http\Request;

class ApplicantSummaryController
{
    public function __invoke(Request $request, ApplicantSummaryQueryData $query, ApplicantSummaryService $service): ApplicantSummaryDocumentData
    {
        return $service->summarize($request->attributes->get('partner'), $query, $request->url());
    }
}
