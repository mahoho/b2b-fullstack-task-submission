<?php

namespace App\Repositories;

use App\Data\ApplicantAggregatesData;
use App\Entities\Applicant;
use App\Entities\Campaign;
use App\Entities\Partner;
use App\Enums\ScoreTier;
use App\Http\Input\ApplicantSummaryQueryData;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends EntityRepository<Applicant>
 */
class ApplicantRepository extends EntityRepository
{
    /**
     * One aggregate query, so the summary costs the same for 10 applicants or 100,000.
     */
    public function aggregates(Campaign $campaign): ApplicantAggregatesData
    {
        $row = $this->createQueryBuilder('a')
            ->select('COUNT(a.id) AS total')
            ->addSelect('COALESCE(SUM(CASE WHEN a.aiScore >= :threshold THEN 1 ELSE 0 END), 0) AS eligible')
            ->addSelect('AVG(a.aiScore) AS average')
            ->where('a.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->setParameter('threshold', $campaign->getEligibilityThreshold())
            ->getQuery()
            ->getSingleResult();

        return ApplicantAggregatesData::fromRow($row);
    }

    /**
     * @return list<Applicant>
     */
    public function topTier(Campaign $campaign): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.campaign = :campaign')
            ->andWhere('a.aiScore >= :topScore')
            ->setParameter('campaign', $campaign)
            ->setParameter('topScore', ScoreTier::TOP_SCORE)
            ->orderBy('a.aiScore', 'DESC')
            ->addOrderBy('a.appliedAt', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->setMaxResults(ScoreTier::TOP_LIMIT)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Applicant>
     */
    public function page(Campaign $campaign, ApplicantSummaryQueryData $filters, int $offset, int $limit): array
    {
        // Databases disagree on where NULL sorts in DESC order, so unscored applicants are pushed last explicitly.
        return $this->filtered($campaign, $filters)
            ->addSelect('CASE WHEN a.aiScore IS NULL THEN 1 ELSE 0 END AS HIDDEN unscoredLast')
            ->orderBy('unscoredLast', 'ASC')
            ->addOrderBy('a.aiScore', 'DESC')
            ->addOrderBy('a.appliedAt', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countFiltered(Campaign $campaign, ApplicantSummaryQueryData $filters): int
    {
        return (int) $this->filtered($campaign, $filters)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findForPartner(string $id, Partner $partner): ?Applicant
    {
        return $this->createQueryBuilder('a')
            ->addSelect('c')
            ->join('a.campaign', 'c')
            ->where('a.id = :id')
            ->andWhere('c.partner = :partner')
            ->setParameter('id', strtolower($id))
            ->setParameter('partner', $partner)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function filtered(Campaign $campaign, ApplicantSummaryQueryData $filters): QueryBuilder
    {
        $query = $this->createQueryBuilder('a')
            ->where('a.campaign = :campaign')
            ->setParameter('campaign', $campaign);

        if ($filters->minScore !== null) {
            $query->andWhere('a.aiScore >= :minScore')->setParameter('minScore', $filters->minScore);
        }

        if ($filters->status !== null) {
            $query->andWhere('a.status = :status')->setParameter('status', $filters->status);
        }

        if ($filters->search !== null) {
            // '!' is the escape character, so % and _ in the term match themselves. A backslash would clash with MySQL string literals.
            $term = strtr(mb_strtolower($filters->search), ['!' => '!!', '%' => '!%', '_' => '!_']);
            $query
                ->andWhere("(LOWER(a.fullName) LIKE :search ESCAPE '!' OR LOWER(a.email) LIKE :search ESCAPE '!')")
                ->setParameter('search', '%'.$term.'%');
        }

        return $query;
    }
}
