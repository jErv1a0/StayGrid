<?php
// src/Controller/User/UserHomeController.php

namespace App\Controller\User; 

use App\Entity\RoomListing;
use App\Entity\LogInUsers;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; 
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')] 
class UserHomeController extends AbstractController 
{
    #[Route('', name: 'app_user_dashboard')] 
    #[IsGranted('ROLE_CLIENT')]
    public function dashboard(EntityManagerInterface $em, BookingRepository $bookingRepository): Response
    {
        /** @var LogInUsers $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $userLocation = trim((string) ($user->getAddress() ?? ''));
        $allRooms = $em->getRepository(RoomListing::class)->findBy([], ['id' => 'DESC']);

        $recommendedRooms = [];
        $seenRoomIds = [];

        // Prefer room recommendations whose location matches the user's city/location.
        if ($userLocation !== '') {
            $needle = mb_strtolower($userLocation);
            foreach ($allRooms as $room) {
                $roomLocation = trim((string) ($room->getLocation() ?? ''));
                if ($roomLocation === '') {
                    continue;
                }

                $haystack = mb_strtolower($roomLocation);
                if (mb_strpos($haystack, $needle) !== false || mb_strpos($needle, $haystack) !== false) {
                    $recommendedRooms[] = $room;
                    $seenRoomIds[$room->getId()] = true;
                }

                if (count($recommendedRooms) >= 3) {
                    break;
                }
            }
        }

        // Backfill recommendations if nearby results are fewer than 3.
        if (count($recommendedRooms) < 3) {
            foreach ($allRooms as $room) {
                if (isset($seenRoomIds[$room->getId()])) {
                    continue;
                }
                $recommendedRooms[] = $room;
                if (count($recommendedRooms) >= 3) {
                    break;
                }
            }
        }

        // UPDATED: Fetch ALL user bookings for full booking history (removed the '5' limit)
        $userBookings = $bookingRepository->findBy(['user' => $user], ['startDate' => 'DESC']);
        $totalBookings = $bookingRepository->count(['user' => $user]);

        $totalSpent = 0.0;
        $bookingCosts = [];

        foreach ($userBookings as $booking) {
            $status = strtolower((string) ($booking->getStatus() ?? ''));

            // Skip cancelled/rejected bookings from expenditure totals.
            if (in_array($status, ['cancelled', 'rejected'], true)) {
                continue;
            }

            $room = $booking->getRoom();
            $bookingType = $booking->getBookingType() ?? 'daily';
            $total = $booking->calculateTotalPrice();

            $duration = 0;
            $unitRate = 0.0;
            $unit = 'night';

            if ($bookingType === 'hourly') {
                $duration = max(1, (int) ($booking->getNumberOfHours() ?? 0));
                $unitRate = (float) ($room?->getPricePerHour() ?? 0);
                $unit = 'hour';
            } else {
                $startDate = $booking->getStartDate();
                $endDate = $booking->getEndDate();
                $duration = ($startDate && $endDate) ? max(1, (int) $startDate->diff($endDate)->days) : 1;
                $unitRate = (float) ($room?->getPricePerNight() ?? 0);
                $unit = 'night';
            }

            $totalSpent += $total;

            if ($booking->getId() !== null) {
                $bookingCosts[$booking->getId()] = [
                    'duration' => $duration,
                    'unit_rate' => $unitRate,
                    'unit' => $unit,
                    'total' => $total,
                ];
            }
        }

        return $this->render('user/user_home/home.html.twig', [
            'page_title' => 'User Dashboard',
            'current_user' => $user,
            'user_location' => $userLocation,
            'total_spent' => $totalSpent,
            'booking_costs' => $bookingCosts,
            'all_rooms' => $allRooms,
            'recommended_rooms' => $recommendedRooms,
            'user_bookings' => $userBookings,
            'total_bookings' => $totalBookings,
        ]);
    }
}