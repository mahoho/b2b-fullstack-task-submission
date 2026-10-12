<?php

use App\Http\Controllers\Api\V1\ApplicantSummaryController;
use App\Http\Controllers\Api\V1\UpdateApplicantStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('partner')->group(function (): void {
    Route::get('campaigns/{campaignId}/applicant-summary', ApplicantSummaryController::class);
    Route::patch('applicants/{applicantId}', UpdateApplicantStatusController::class);
});
