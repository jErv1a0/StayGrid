<?php

namespace App\Controller\Api;

use App\Entity\Booking;
use App\Entity\LogInUsers;
use App\Entity\RoomListing;
use App\Repository\BookingRepository;
use App\Repository\RoomListingRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class AppStatusController extends AbstractController
{
    #[Route('/status', name: 'app_status', methods: ['GET'])]
    public function status(Connection $connection): JsonResponse
    {
        try {
            $connection->executeQuery('SELECT 1');
            $dbStatus = 'connected';
        } catch (\Exception $e) {
            $dbStatus = 'disconnected: ' . $e->getMessage();
        }

        return $this->json([
            'success' => true,
            'data' => [
                'project' => 'StayGrid',
                'status' => 'online',
                'version' => '1.0.0',
                'server_time' => date('c'),
                'database' => $dbStatus,
            ],
        ]);
    }

    #[Route('/rooms', name: 'api_rooms', methods: ['GET'])]
    public function getRooms(RoomListingRepository $roomListingRepository): JsonResponse
    {
        $rooms = array_map(function (RoomListing $room) {
            return $this->serializeRoom($room);
        }, $roomListingRepository->findBy(['isBlocked' => false]));

        return $this->json([
            'success' => true,
            'data' => $rooms,
            'meta' => ['count' => count($rooms)],
        ]);
    }

    #[Route('/bookings', name: 'api_bookings', methods: ['GET'])]
    public function getBookings(BookingRepository $bookingRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof LogInUsers) {
            return $this->json(['success' => false, 'error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true) || in_array('ROLE_STAFF', $user->getRoles(), true)) {
            $bookings = $bookingRepository->findAll();
        } else {
            $bookings = $bookingRepository->findBy(['user' => $user]);
        }

        $bookingsData = array_map(function (Booking $booking) {
            return $this->serializeBooking($booking);
        }, $bookings);

        return $this->json([
            'success' => true,
            'data' => $bookingsData,
            'meta' => ['count' => count($bookingsData)],
        ]);
    }

    #[Route('/bookings', name: 'api_bookings_create', methods: ['POST'])]
    public function createBooking(
        Request $request,
        RoomListingRepository $roomListingRepository,
        BookingRepository $bookingRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof LogInUsers) {
            return $this->json(['success' => false, 'error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true);

        if (!$payload || !isset($payload['roomId'], $payload['startDate'], $payload['endDate'])) {
            return $this->json([
                'success' => false,
                'error' => 'roomId, startDate and endDate are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $room = $roomListingRepository->find($payload['roomId']);

        if (!$room) {
            return $this->json(['success' => false, 'error' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }

        if ($room->isBlocked() || !$room->isAvailable()) {
            return $this->json(['success' => false, 'error' => 'Room is not available for booking'], Response::HTTP_CONFLICT);
        }

        try {
            $start = new \DateTimeImmutable($payload['startDate']);
            $end = new \DateTimeImmutable($payload['endDate']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD'], Response::HTTP_BAD_REQUEST);
        }

        if ($end <= $start) {
            return $this->json(['success' => false, 'error' => 'endDate must be after startDate'], Response::HTTP_BAD_REQUEST);
        }

        $overlapCount = $bookingRepository->countOverlappingBookings($room, $start, $end);

        if ($overlapCount > 0) {
            return $this->json(['success' => false, 'error' => 'Room already booked for this date range'], Response::HTTP_CONFLICT);
        }

        $booking = new Booking();
        $booking->setRoom($room);
        $booking->setStartDate($start);
        $booking->setEndDate($end);
        $booking->setStatus('confirmed');
        $booking->setUser($user);

        $entityManager->persist($booking);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => $this->serializeBooking($booking),
        ], Response::HTTP_CREATED);
    }

    private function serializeRoom(RoomListing $room): array
    {
        return [
            'id' => $room->getId(),
            'number' => $room->getNumber(),
            'title' => $room->getTitle(),
            'category' => $room->getCategory(),
            'description' => $room->getDescription(),
            'capacity' => $room->getCapacity(),
            'pricePerNight' => $room->getPricePerNight(),
            'isAvailable' => $room->isAvailable(),
            'isBlocked' => $room->isBlocked(),
            'image' => $room->getImage(),
            'location' => $room->getLocation(),
            'startDate' => $room->getStartDate()?->format('Y-m-d'),
            'endDate' => $room->getEndDate()?->format('Y-m-d'),
        ];
    }

    private function serializeBooking(Booking $booking): array
    {
        $room = $booking->getRoom();

        return [
            'id' => $booking->getId(),
            'room' => $room ? $this->serializeRoom($room) : null,
            'startDate' => $booking->getStartDate()?->format('Y-m-d'),
            'endDate' => $booking->getEndDate()?->format('Y-m-d'),
            'status' => $booking->getStatus(),
            'user' => $booking->getUser() ? [
                'id' => $booking->getUser()->getId(),
                'email' => $booking->getUser()->getEmail(),
                'fullName' => $booking->getUser()->getFullName(),
            ] : null,
        ];
    }
}
