<?php

namespace App\Repositories;

use App\Entities\Applicant;
use App\Entities\Campaign;
use App\Entities\Partner;
use App\Enums\ScoreTier;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends EntityRepository<Applicant>
 */
class ApplicantRepository extends EntityRepository
{
    /**
     * One aggregate query, so the summary costs the same for 10 applicants or 100,000.
     *
     * @return array{total: int, eligible: int, average: float|string|null}
     */
    public function aggregates(Campaign $campaign): array
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

        return ['total' => (int) $row['total'], 'eligible' => (int) $row['eligible'], 'average' => $row['average']];
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
    public function page(Campaign $campaign, ?int $minScore, int $offset, int $limit): array
    {
        // Databases disagree on where NULL sorts in DESC order, so unscored applicants are pushed last explicitly.
        return $this->filtered($campaign, $minScore)
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

    public function countFiltered(Campaign $campaign, ?int $minScore): int
    {
        return (int) $this->filtered($campaign, $minScore)
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

    private function filtered(Campaign $campaign, ?int $minScore): QueryBuilder
    {
        $query = $this->createQueryBuilder('a')
            ->where('a.campaign = :campaign')
            ->setParameter('campaign', $campaign);

        if ($minScore !== null) {
            $query->andWhere('a.aiScore >= :minScore')->setParameter('minScore', $minScore);
        }

        return $query;
    }
}
