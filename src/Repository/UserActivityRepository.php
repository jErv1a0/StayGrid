<?php

namespace App\Repository;

use App\Entity\UserActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserActivity>
 */
class UserActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserActivity::class);
    }

    /**
     * Returns activity rows as scalar data so rendering does not hydrate broken user proxies.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findRecentForAdminList(int $limit = 200): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.id AS id')
            ->addSelect('a.createdAt AS createdAt')
            ->addSelect('a.action AS action')
            ->addSelect('a.ip AS ip')
            ->addSelect('a.userAgent AS userAgent')
            ->addSelect('IDENTITY(a.user) AS userId')
            ->addSelect('u.email AS userEmail')
            ->leftJoin('a.user', 'u')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
