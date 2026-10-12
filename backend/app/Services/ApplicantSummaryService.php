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
use App\Http\Input\ApplicantSummaryQueryData;
use App\Repositories\ApplicantRepository;
use Doctrine\ORM\EntityManagerInterface;

class ApplicantSummaryService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function summarize(Campaign $campaign, ApplicantSummaryQueryData $query, string $url): ApplicantSummaryDocumentData
    {
        /** @var ApplicantRepository $applicants */
        $applicants = $this->em->getRepository(Applicant::class);
        $threshold = $campaign->getEligibilityThreshold();

        $summary = ApplicantSummaryData::fromAggregates(
            $applicants->aggregates($campaign),
            array_map(
                fn (Applicant $applicant) => new TopTierApplicantData($applicant->getId(), $applicant->getFullName(), (int) $applicant->getAiScore()),
                $applicants->topTier($campaign),
            ),
        );

        $total = $applicants->countFiltered($campaign, $query);
        $lastPage = max(1, (int) ceil($total / $query->pageSize));

        // Past the last page there is nothing to fetch, and skipping the query also keeps a huge page[number] from overflowing the offset.
        $rows = $query->pageNumber > $lastPage
            ? []
            : $applicants->page($campaign, $query, ($query->pageNumber - 1) * $query->pageSize, $query->pageSize);

        return new ApplicantSummaryDocumentData(
            data: array_map(fn (Applicant $applicant) => ApplicantResourceData::fromEntity($applicant, $threshold), $rows),
            meta: new ApplicantSummaryMetaData($summary, new PageMetaData($query->pageNumber, $query->pageSize, $total, $lastPage)),
            links: $this->links($url, $query, $lastPage),
        );
    }

    private function links(string $url, ApplicantSummaryQueryData $query, int $lastPage): PaginationLinksData
    {
        $to = function (int $number) use ($url, $query): string {
            $filters = ($query->minScore === null ? [] : ['min_score' => $query->minScore])
                + ($query->status === null ? [] : ['status' => $query->status->value])
                + ($query->search === null ? [] : ['search' => $query->search]);

            // Filter values stay percent-encoded, so a search containing & or # cannot break the link. Only the page brackets are literal.
            $queryString = http_build_query($filters, '', '&', PHP_QUERY_RFC3986);
            $paging = "page[number]={$number}&page[size]={$query->pageSize}";

            return $url.'?'.($queryString === '' ? '' : $queryString.'&').$paging;
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
