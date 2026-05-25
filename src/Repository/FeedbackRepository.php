<?php

namespace App\Repository;

use App\Entity\Feedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feedback>
 */
class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    /**
     * Return approved feedback ordered by newest first.
     *
     * @return Feedback[]
     */
    public function findApproved(int $limit = 50): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.approved = :approved')
            ->setParameter('approved', true)
            ->orderBy('f.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Return pending feedback awaiting admin approval.
     *
     * @return Feedback[]
     */
    public function findPending(int $limit = 100): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.approved = :approved')
            ->setParameter('approved', false)
            ->orderBy('f.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Feedback[] Returns an array of Feedback objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Feedback
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
