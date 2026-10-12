<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\ApplicantDocumentData;
use App\Data\ApplicantResourceData;
use App\Entities\Applicant;
use App\Http\Requests\UpdateApplicantStatusRequest;
use Doctrine\ORM\EntityManagerInterface;

class UpdateApplicantStatusController
{
    public function __invoke(Applicant $applicant, UpdateApplicantStatusRequest $request, EntityManagerInterface $em): ApplicantDocumentData
    {
        $applicant->setStatus($request->toData()->status);
        $em->flush();

        return new ApplicantDocumentData(
            ApplicantResourceData::fromEntity($applicant, $applicant->getCampaign()->getEligibilityThreshold()),
        );
    }
}
