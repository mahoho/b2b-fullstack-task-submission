<?php

// app/Repositories/ApplicantRepository.php

namespace App\Repositories;

use App\Entities\Applicant;
use App\Entities\Campaign;
use App\Enums\ScoreTier;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends EntityRepository<Applicant>
 */
class ApplicantRepository extends EntityRepository
{
    /**
     * @return array{total: int, eligible: int, average: float|string|null}
     */
    public function aggregates(Campaign $campaign): array
    {
        $row = $this->createQueryBuilder('a')
            ->select(
                'COUNT(a.id) AS total',
                'COALESCE(SUM(CASE WHEN a.aiScore >= :threshold THEN 1 ELSE 0 END), 0) AS eligible',
                'AVG(a.aiScore) AS average',
            )
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
            ->andWhere('a.aiScore >= :top')
            ->setParameter('campaign', $campaign)
            ->setParameter('top', ScoreTier::TOP_SCORE)
            ->orderBy('a.aiScore', 'DESC')
            ->addOrderBy('a.id', 'ASC')
            ->setMaxResults(ScoreTier::TOP_LIMIT)
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

    /**
     * Highest score first, unscored last on every platform, id as a tie-breaker so pages never overlap.
     *
     * @return list<Applicant>
     */
    public function page(Campaign $campaign, ?int $minScore, int $pageSize, int $pageNumber): array
    {
        return $this->filtered($campaign, $minScore)
            ->addSelect('CASE WHEN a.aiScore IS NULL THEN 1 ELSE 0 END AS HIDDEN unscored')
            ->orderBy('unscored', 'ASC')
            ->addOrderBy('a.aiScore', 'DESC')
            ->addOrderBy('a.id', 'ASC')
            ->setFirstResult(($pageNumber - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();
    }

    private function filtered(Campaign $campaign, ?int $minScore): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.campaign = :campaign')
            ->setParameter('campaign', $campaign);

        if ($minScore !== null) {
            $qb->andWhere('a.aiScore >= :minScore')->setParameter('minScore', $minScore);
        }

        return $qb;
    }
}
