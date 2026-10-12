<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\ApplicantDocumentData;
use App\Data\ApplicantResourceData;
use App\Entities\Applicant;
use App\Http\Requests\UpdateApplicantStatusRequest;
use App\Repositories\ApplicantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateApplicantStatusController
{
    public function __invoke(UpdateApplicantStatusRequest $request, EntityManagerInterface $em): ApplicantDocumentData
    {
        $input = $request->toData();

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
