<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\ApplicantDocumentData;
use App\Data\ApplicantResourceData;
use App\Entities\Applicant;
use App\Http\Input\UpdateApplicantStatusData;
use App\Repositories\ApplicantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateApplicantStatusController
{
    public function __invoke(Request $request, UpdateApplicantStatusData $input, EntityManagerInterface $em): ApplicantDocumentData
    {
        /** @var ApplicantRepository $applicants */
        $applicants = $em->getRepository(Applicant::class);
        $applicant = $applicants->findForPartner($input->applicantId, $request->attributes->get('partner'))
            ?? throw new NotFoundHttpException('Applicant not found.');

        $applicant->setStatus($input->status);
        $em->flush();

        return new ApplicantDocumentData(
            ApplicantResourceData::fromEntity($applicant, $applicant->getCampaign()->getEligibilityThreshold()),
        );
    }
}
