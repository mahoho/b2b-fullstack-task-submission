<?php

use App\Http\Controllers\Api\V1\ApplicantSummaryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('partner')->group(function (): void {
    Route::get('campaigns/{campaignId}/applicant-summary', ApplicantSummaryController::class);
});
