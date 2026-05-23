<?php

namespace App\Controller\Admin;

use App\Entity\RoomListing;
use App\Entity\Transaction;
use App\Entity\LogInUsers;
use App\Repository\BookingRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminHomeController extends AbstractController
{
    #[Route('', name: 'app_admin_home')]
    public function home(
        EntityManagerInterface $em,
        BookingRepository $bookingRepository,
        TransactionRepository $transactionRepository
    ): Response {
        $rooms = $em->getRepository(RoomListing::class)->findAll();
        $totalRooms = count($rooms);

        $now = new \DateTimeImmutable();
        $activeBookings = $bookingRepository->findActiveBookings($now);
        $occupiedRoomIds = array_map(fn($b) => $b->getRoom()->getId(), $activeBookings);
        $occupiedRoomsCount = count($occupiedRoomIds);
        $availableRoomsCount = $totalRooms - $occupiedRoomsCount;

        $roomCategoryStats = [];
        foreach ($rooms as $room) {
            $category = $room->getCategory() ?: 'Other';
            if (!isset($roomCategoryStats[$category])) {
                $roomCategoryStats[$category] = ['total' => 0, 'occupied' => 0, 'available' => 0];
            }
            $roomCategoryStats[$category]['total']++;
            if (in_array($room->getId(), $occupiedRoomIds)) {
                $roomCategoryStats[$category]['occupied']++;
            } else {
                $roomCategoryStats[$category]['available']++;
            }
        }

        $allBookings = $bookingRepository->findAll();
        $totalRevenue = 0.0;

        $bookingStatusCounts = [
            'confirmed' => 0,
            'pending' => 0,
            'cancelled' => 0,
            'rejected' => 0,
            'other' => 0,
        ];

        $monthlyRevenueMap = [];
        $monthlyBookingMap = [];
        $monthLabels = [];
        $baseMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        for ($i = 5; $i >= 0; $i--) {
            $month = $baseMonth->modify('-' . $i . ' months');
            $key = $month->format('Y-m');
            $monthLabels[] = strtoupper($month->format('M Y'));
            $monthlyRevenueMap[$key] = 0.0;
            $monthlyBookingMap[$key] = 0;
        }

        foreach ($allBookings as $booking) {
            $status = strtolower((string) ($booking->getStatus() ?? ''));

            if (array_key_exists($status, $bookingStatusCounts)) {
                $bookingStatusCounts[$status]++;
            } else {
                $bookingStatusCounts['other']++;
            }

            $startDate = $booking->getStartDate();
            if ($startDate) {
                $monthKey = $startDate->format('Y-m');
                if (array_key_exists($monthKey, $monthlyBookingMap)) {
                    $monthlyBookingMap[$monthKey]++;
                }
            }

            // Exclude non-revenue bookings from gross revenue.
            if (in_array($status, ['cancelled', 'rejected'], true)) {
                continue;
            }

            $bookingRevenue = (float) $booking->calculateTotalPrice();
            $totalRevenue += $bookingRevenue;

            if ($startDate) {
                $monthKey = $startDate->format('Y-m');
                if (array_key_exists($monthKey, $monthlyRevenueMap)) {
                    $monthlyRevenueMap[$monthKey] += $bookingRevenue;
                }
            }
        }

        // Use custom query to only get transactions with existing users
        $qb = $em->createQueryBuilder();
        $qb->select('t')
           ->from(Transaction::class, 't')
           ->innerJoin('t.user', 'u')  // Only get transactions where user exists
           ->orderBy('t.createdAt', 'DESC')
           ->setMaxResults(10);
        
        $recentTransactions = $qb->getQuery()->getResult();

        $activeUserCount = $em->getRepository(LogInUsers::class)->count([]);

        return $this->render('admin/admin_home/home.html.twig', [
            'page_title' => 'Admin Dashboard',
            'rooms' => $rooms,
            'transactions' => $recentTransactions,
            'total_rooms' => $totalRooms,
            'available_rooms' => $availableRoomsCount,
            'occupied_rooms' => $occupiedRoomsCount,
            'occupied_room_ids' => $occupiedRoomIds,
            'total_revenue' => $totalRevenue,
            'room_category_stats' => $roomCategoryStats,
            'active_user_count' => $activeUserCount,
            'booking_status_counts' => $bookingStatusCounts,
            'monthly_labels' => $monthLabels,
            'monthly_revenue_data' => array_map(static fn($v) => round($v, 2), array_values($monthlyRevenueMap)),
            'monthly_booking_data' => array_values($monthlyBookingMap),
        ]);
    }
}
