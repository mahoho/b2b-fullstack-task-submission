<?php

// app/Services/ApplicantSummaryService.php

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
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function summarize(Partner $partner, ApplicantSummaryQueryData $query, string $url): ApplicantSummaryDocumentData
    {
        $campaign = $this->em->getRepository(Campaign::class)->findOneBy([
            'id' => $query->campaignId,
            'partner' => $partner,
        ]) ?? throw new NotFoundHttpException('Campaign not found.');

        /** @var ApplicantRepository $applicants */
        $applicants = $this->em->getRepository(Applicant::class);

        $aggregates = $applicants->aggregates($campaign);
        $topTier = array_map(
            fn (Applicant $a) => new TopTierApplicantData($a->getId(), $a->getFullName(), $a->getAiScore()),
            $applicants->topTier($campaign),
        );

        $threshold = $campaign->getEligibilityThreshold();
        $total = $applicants->countFiltered($campaign, $query->minScore);
        $rows = $applicants->page($campaign, $query->minScore, $query->pageSize, $query->pageNumber);

        $lastPage = max(1, (int) ceil($total / $query->pageSize));

        return new ApplicantSummaryDocumentData(
            data: array_map(fn (Applicant $a) => ApplicantResourceData::fromEntity($a, $threshold), $rows),
            meta: new ApplicantSummaryMetaData(
                summary: ApplicantSummaryData::fromAggregates(
                    $aggregates['total'],
                    $aggregates['eligible'],
                    $aggregates['average'],
                    $topTier,
                ),
                page: new PageMetaData($query->pageNumber, $query->pageSize, $total, $lastPage),
            ),
            links: new PaginationLinksData(
                self: $this->link($url, $query, $query->pageNumber),
                first: $this->link($url, $query, 1),
                prev: $query->pageNumber > 1 ? $this->link($url, $query, min($query->pageNumber - 1, $lastPage)) : null,
                next: $query->pageNumber < $lastPage ? $this->link($url, $query, $query->pageNumber + 1) : null,
                last: $this->link($url, $query, $lastPage),
            ),
        );
    }

    // Rebuilds the query string so min_score survives paging; any incoming page params are replaced.
    private function link(string $url, ApplicantSummaryQueryData $query, int $number): string
    {
        $params = $query->minScore !== null ? ['min_score' => $query->minScore] : [];
        $params['page'] = ['number' => $number, 'size' => $query->pageSize];

        return strtok($url, '?').'?'.http_build_query($params);
    }
}
