<?php

namespace App\Services;

use App\Data\ApplicantResourceData;
use App\Data\ApplicantSummaryData;
use App\Data\ApplicantSummaryDocumentData;
use App\Data\ApplicantSummaryMetaData;
use App\Data\PageMetaData;
use App\Data\PaginationLinksData;
use App\Data\TopTierApplicantData;
use App\Entities\Applicant;
use App\Entities\Campaign;
use App\Entities\Partner;
use App\Http\Input\ApplicantSummaryQueryData;
use App\Repositories\ApplicantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApplicantSummaryService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function summarize(Partner $partner, ApplicantSummaryQueryData $query, string $url): ApplicantSummaryDocumentData
    {
        $campaign = $this->em->getRepository(Campaign::class)->findOneBy([
            'id' => strtolower($query->campaignId),
            'partner' => $partner,
        ]) ?? throw new NotFoundHttpException('Campaign not found.');

        /** @var ApplicantRepository $applicants */
        $applicants = $this->em->getRepository(Applicant::class);
        $threshold = $campaign->getEligibilityThreshold();

        $aggregates = $applicants->aggregates($campaign);
        $summary = ApplicantSummaryData::fromAggregates(
            $aggregates['total'],
            $aggregates['eligible'],
            $aggregates['average'],
            array_map(
                fn (Applicant $applicant) => new TopTierApplicantData($applicant->getId(), $applicant->getFullName(), (int) $applicant->getAiScore()),
                $applicants->topTier($campaign),
            ),
        );

        $total = $applicants->countFiltered($campaign, $query->minScore);
        $lastPage = max(1, (int) ceil($total / $query->pageSize));

        // Past the last page there is nothing to fetch, and skipping the query also keeps a huge page[number] from overflowing the offset.
        $rows = $query->pageNumber > $lastPage
            ? []
            : $applicants->page($campaign, $query->minScore, ($query->pageNumber - 1) * $query->pageSize, $query->pageSize);

        return new ApplicantSummaryDocumentData(
            data: array_map(fn (Applicant $applicant) => ApplicantResourceData::fromEntity($applicant, $threshold), $rows),
            meta: new ApplicantSummaryMetaData($summary, new PageMetaData($query->pageNumber, $query->pageSize, $total, $lastPage)),
            links: $this->links($url, $query, $lastPage),
        );
    }

    private function links(string $url, ApplicantSummaryQueryData $query, int $lastPage): PaginationLinksData
    {
        $to = function (int $number) use ($url, $query): string {
            $params = ($query->minScore === null ? [] : ['min_score' => $query->minScore])
                + ['page' => ['number' => $number, 'size' => $query->pageSize]];

            return $url.'?'.urldecode(http_build_query($params));
        };

        return new PaginationLinksData(
            self: $to($query->pageNumber),
            first: $to(1),
            prev: $query->pageNumber > 1 ? $to(min($query->pageNumber - 1, $lastPage)) : null,
            next: $query->pageNumber < $lastPage ? $to($query->pageNumber + 1) : null,
            last: $to($lastPage),
        );
    }
}
