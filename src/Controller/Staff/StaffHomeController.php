<?php

namespace App\Controller\Staff;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff')]
#[IsGranted('ROLE_STAFF')]
class StaffHomeController extends AbstractController
{
    #[Route('', name: 'app_staff_dashboard')]
    #[Route('/dashboard', name: 'app_staff_dashboard_alias')]
    public function home(\App\Repository\BookingRepository $bookingRepository, \App\Repository\RoomListingRepository $roomRepo): Response
    {
        // Fetch all bookings sorted by start date (newest first)
        $bookings = $bookingRepository->findBy([], ['startDate' => 'DESC']);

        // Fetch all rooms for dashboard stats
        $rooms = $roomRepo->findAll();

        // Count pending bookings
        $pendingCount = count($bookingRepository->findBy(['status' => 'pending']));

        // Booking status report
        $bookingStatusCounts = [
            'confirmed' => 0,
            'pending' => 0,
            'cancelled' => 0,
            'rejected' => 0,
            'other' => 0,
        ];

        // Booking type report
        $bookingTypeCounts = [
            'daily' => 0,
            'hourly' => 0,
        ];

        // Monthly booking trend (last 6 months)
        $monthBuckets = [];
        $monthLabels = [];
        $now = new \DateTimeImmutable('first day of this month 00:00:00');
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->modify('-' . $i . ' months');
            $key = $month->format('Y-m');
            $monthBuckets[$key] = 0;
            $monthLabels[] = strtoupper($month->format('M Y'));
        }

        $upcomingCheckins = 0;
        $upcomingWindowEnd = (new \DateTimeImmutable('now'))->modify('+7 days');

        foreach ($bookings as $booking) {
            $status = strtolower((string) $booking->getStatus());
            if (array_key_exists($status, $bookingStatusCounts)) {
                $bookingStatusCounts[$status]++;
            } else {
                $bookingStatusCounts['other']++;
            }

            $type = strtolower((string) $booking->getBookingType());
            if (array_key_exists($type, $bookingTypeCounts)) {
                $bookingTypeCounts[$type]++;
            }

            $startDate = $booking->getStartDate();
            if ($startDate) {
                $monthKey = $startDate->format('Y-m');
                if (array_key_exists($monthKey, $monthBuckets)) {
                    $monthBuckets[$monthKey]++;
                }

                if ($status === 'confirmed') {
                    $start = \DateTimeImmutable::createFromInterface($startDate);
                    if ($start >= new \DateTimeImmutable('now') && $start <= $upcomingWindowEnd) {
                        $upcomingCheckins++;
                    }
                }
            }
        }

        // Room utilization report
        $roomStatusCounts = [
            'available' => 0,
            'occupied' => 0,
            'other' => 0,
        ];

        foreach ($rooms as $room) {
            $roomStatus = strtolower((string) $room->getStatus());
            if ($roomStatus === 'available') {
                $roomStatusCounts['available']++;
            } elseif ($roomStatus === 'occupied') {
                $roomStatusCounts['occupied']++;
            } else {
                $roomStatusCounts['other']++;
            }
        }

        $totalRooms = count($rooms);
        $occupancyRate = $totalRooms > 0 ? round(($roomStatusCounts['occupied'] / $totalRooms) * 100, 1) : 0;

        return $this->render('staff/staff_home/home.html.twig', [
            'page_title' => 'Staff Dashboard',
            'current_user' => $this->getUser(),
            'bookings' => $bookings,
            'rooms' => $rooms,
            'pendingCount' => $pendingCount,
            'bookingStatusCounts' => $bookingStatusCounts,
            'bookingTypeCounts' => $bookingTypeCounts,
            'monthlyBookingLabels' => $monthLabels,
            'monthlyBookingData' => array_values($monthBuckets),
            'roomStatusCounts' => $roomStatusCounts,
            'occupancyRate' => $occupancyRate,
            'upcomingCheckins' => $upcomingCheckins,
        ]);
    }

    // Handle trailing-slash access to /staff/ by redirecting to the canonical /staff route
    #[Route('/', name: 'app_staff_dashboard_slash')]
    public function slash(): Response
    {
        return $this->redirectToRoute('app_staff_dashboard');
    }
}
