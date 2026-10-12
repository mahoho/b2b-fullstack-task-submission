// app/Http/Controllers/Api/V1/ApplicantSummaryController.php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\ApplicantSummaryDocumentData;
use App\Entities\Campaign;
use App\Http\Requests\ApplicantSummaryRequest;
use App\Services\ApplicantSummaryService;

class ApplicantSummaryController
{
    public function __invoke(Campaign $campaign, ApplicantSummaryRequest $request, ApplicantSummaryService $service): ApplicantSummaryDocumentData
    {
        return $service->summarize($campaign, $request->toData(), $request->url());
    }
}
