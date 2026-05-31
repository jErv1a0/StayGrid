<?php

namespace App\Controller\Api;

use App\Entity\Booking;
use App\Entity\LogInUsers;
use App\Repository\BookingRepository;
use App\Repository\RoomListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/api/bookings')]
class BookingApiController extends AbstractController
{
    private BookingRepository $bookingRepository;
    private RoomListingRepository $roomRepo;
    private EntityManagerInterface $em;
    private \App\Service\ActivityLogger $activityLogger;

    public function __construct(BookingRepository $bookingRepository, RoomListingRepository $roomRepo, EntityManagerInterface $em, \App\Service\ActivityLogger $activityLogger)
    {
        $this->bookingRepository = $bookingRepository;
        $this->roomRepo = $roomRepo;
        $this->em = $em;
        $this->activityLogger = $activityLogger;
    }

    #[Route('/my', name: 'api_bookings_my', methods: ['GET'], priority: 100)]
    public function myBookings(): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $bookings = $this->bookingRepository->findBy(['user' => $user], ['startDate' => 'DESC']);

        $data = array_map(function(Booking $b) {
            $room = $b->getRoom();
            return [
                'id' => $b->getId(),
                'room_id' => $room ? $room->getId() : null,
                'room_name' => $room ? $room->getTitle() : null,
                'check_in' => $b->getStartDate() ? $b->getStartDate()->format(DATE_ATOM) : null,
                'check_out' => $b->getEndDate() ? $b->getEndDate()->format(DATE_ATOM) : null,
                'guests' => $b->getGuests() ?? ($room ? $room->getCapacity() : null),
                'status' => $b->getStatus(),
                'total_price' => $b->calculateTotalPrice(),
            ];
        }, $bookings);

        return new JsonResponse(array_values($data));
    }

    #[Route('', name: 'api_bookings_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $roomId = $payload['room_id'] ?? null;
        $checkIn = $payload['check_in'] ?? null;
        $checkOut = $payload['check_out'] ?? null;
        $guests = isset($payload['guests']) ? (int)$payload['guests'] : null;

        if (!$roomId || !$checkIn || !$checkOut) {
            return new JsonResponse(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $room = $this->roomRepo->find($roomId);
        if (!$room) {
            return new JsonResponse(['error' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$room->isAvailable() || $room->isBlocked()) {
            return new JsonResponse(['error' => 'Room unavailable'], Response::HTTP_CONFLICT);
        }

        try {
            $start = new \DateTimeImmutable($checkIn);
            $end = new \DateTimeImmutable($checkOut);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
        }

        if ($end <= $start) {
            return new JsonResponse(['error' => 'End date must be after start date'], Response::HTTP_BAD_REQUEST);
        }

        // Check overlapping bookings
        $overlaps = $this->bookingRepository->countOverlappingBookings($room, $start, $end);
        if ($overlaps > 0) {
            return new JsonResponse(['error' => 'This time slot is already booked'], Response::HTTP_CONFLICT);
        }

        $booking = new Booking();
        $booking->setRoom($room);
        $booking->setUser($user);
        $booking->setStartDate($start);
        $booking->setEndDate($end);
        $booking->setStatus('confirmed');
        if ($guests !== null) {
            $booking->setGuests($guests);
        }

        $this->em->persist($booking);
        $this->em->flush();

        $this->activityLogger->log('booking.created', $user, $booking, ['room' => $room->getId()]);

        $roomObj = $booking->getRoom();
        $result = [
            'id' => $booking->getId(),
            'room_id' => $roomObj ? $roomObj->getId() : null,
            'room_name' => $roomObj ? $roomObj->getTitle() : null,
            'check_in' => $booking->getStartDate() ? $booking->getStartDate()->format(DATE_ATOM) : null,
            'check_out' => $booking->getEndDate() ? $booking->getEndDate()->format(DATE_ATOM) : null,
            'guests' => $booking->getGuests() ?? ($roomObj ? $roomObj->getCapacity() : null),
            'status' => $booking->getStatus(),
            'total_price' => $booking->calculateTotalPrice(),
        ];

        return new JsonResponse($result, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_bookings_cancel', methods: ['DELETE'])]
    public function cancel(int $id): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $booking = $this->bookingRepository->find($id);
        if (!$booking) {
            return new JsonResponse(['error' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        if ($booking->getUser() && $booking->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // Mark as cancelled and persist
        $booking->setStatus('cancelled');
        $this->em->persist($booking);
        $this->em->flush();

        $this->activityLogger->log('booking.cancelled', $user, $booking, ['id' => $booking->getId()]);

        return new JsonResponse(['message' => 'Booking cancelled'], Response::HTTP_OK);
    }

    private function resolveAuthenticatedUser(): ?LogInUsers
    {
        $tokenUser = $this->getUser();
        if ($tokenUser instanceof LogInUsers) {
            return $tokenUser;
        }

        if (method_exists($this, 'getRequestStack')) {
            $request = $this->container->get('request_stack')->getCurrentRequest();
            if ($request && $request->hasSession()) {
                $sessionUserId = $request->getSession()->get('api_user_id');
                if ($sessionUserId) {
                    $entityManager = $this->container->get('doctrine')->getManager();
                    $user = $entityManager->getRepository(LogInUsers::class)->find((int) $sessionUserId);
                    if ($user instanceof LogInUsers) {
                        return $user;
                    }
                }
            }
        }

        return null;
    }
}
