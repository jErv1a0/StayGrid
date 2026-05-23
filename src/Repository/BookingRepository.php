<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\RoomListing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * Count bookings for a room that overlap the provided date range.
     * Overlap condition: existing.startDate < end AND existing.endDate > start
     */
    public function countOverlappingBookings(RoomListing $room, \DateTimeInterface $start, \DateTimeInterface $end, ?int $excludeBookingId = null): int
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.room = :room')
            ->andWhere('b.startDate < :end')
            ->andWhere('b.endDate > :start')
            ->andWhere("(b.status IS NULL OR b.status NOT IN ('cancelled','rejected'))")
            ->setParameter('room', $room)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($excludeBookingId !== null) {
            $qb->andWhere('b.id != :excludeBookingId')
                ->setParameter('excludeBookingId', $excludeBookingId);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns all active and confirmed bookings for a given datetime
     */
    public function findActiveBookings(\DateTimeImmutable $now)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->andWhere('b.startDate <= :now AND b.endDate >= :now')
            ->setParameter('status', 'confirmed')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
