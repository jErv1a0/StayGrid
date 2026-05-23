<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\LogInUsers;
use App\Entity\RoomListing;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ApiAuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EmailVerifier $emailVerifier
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['fullName'])) {
            return $this->json([
                'success' => false,
                'error' => 'Email, password, and fullName are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $entityManager->getRepository(LogInUsers::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            return $this->json([
                'success' => false,
                'error' => 'User already exists'
            ], Response::HTTP_CONFLICT);
        }

        $user = new LogInUsers();
        $user->setEmail($data['email']);
        $user->setFullName($data['fullName']);
        $user->setRoles(['ROLE_CLIENT']);
        $user->setIsVerified(false);

        if (!empty($data['phoneNumber'])) {
            $user->setPhoneNumber($data['phoneNumber']);
        }

        if (!empty($data['address'])) {
            $user->setAddress($data['address']);
        }

        if (!empty($data['birthday'])) {
            try {
                $user->setBirthday(new \DateTime($data['birthday']));
            } catch (\Exception $e) {}
        }

        $user->setPassword(
            $passwordHasher->hashPassword($user, $data['password'])
        );

        $entityManager->persist($user);
        $entityManager->flush();

        try {
            $senderEmail = $_ENV['MAILER_SENDER'] ?? 'test@gmail.com';

            $emailVerifier->sendEmailConfirmation(
                'api_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address($senderEmail, 'StayGrid Dev'))
                    ->to($user->getEmail())
                    ->subject('Verify your email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => 'User saved but email failed'
            ]);
        }

        return $this->json([
            'success' => true,
            'message' => 'Registered. Check email.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'isVerified' => false
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['GET'])]
    public function verifyEmail(
        Request $request,
        EmailVerifier $emailVerifier,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $userId = $request->query->get('id');

            if (!$userId) {
                return $this->json(['success' => false, 'error' => 'Missing ID'], 400);
            }

            $user = $entityManager->getRepository(LogInUsers::class)->find($userId);

            if (!$user) {
                return $this->json(['success' => false, 'error' => 'User not found'], 404);
            }

            $emailVerifier->handleEmailConfirmation($request, $user);

            return $this->json([
                'success' => true,
                'message' => 'Email verified'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Verification failed'
            ], 400);
        }
    }

    #[Route('/api/login', name: 'api_login_info', methods: ['GET'])]
    public function loginInfo(): Response
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'Use POST /api/login with JSON body: {"email":"...","password":"..."}'
        ]);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Email and password required'
            ], 400);
        }

        $user = $entityManager->getRepository(LogInUsers::class)
            ->findOneBy(['email' => $data['email']]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->isVerified()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Verify your email first'
            ], 403);
        }

        // SIMPLE TOKEN (for exam/demo only)
        $token = base64_encode($user->getId() . ':' . $user->getEmail());

        // Keep a session-based fallback for mobile clients that reuse cookies.
        if ($request->hasSession()) {
            $request->getSession()->set('api_user_id', $user->getId());
        }

        return new JsonResponse([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName()
            ]
        ]);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader) {
            return new JsonResponse(['success' => false, 'error' => 'No token'], 401);
        }

        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = base64_decode($token);

        if (!$decoded || !str_contains($decoded, ':')) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid token'], 401);
        }

        [$userId, $email] = explode(':', $decoded);

        $user = $entityManager->getRepository(LogInUsers::class)->find($userId);

        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'User not found'], 404);
        }

        return new JsonResponse([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'phoneNumber' => $user->getPhoneNumber(),
                'address' => $user->getAddress(),
                'birthday' => $user->getBirthday()?->format('Y-m-d'),
                'roles' => $user->getRoles(),
                'isVerified' => $user->isVerified()
            ]
        ]);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): Response
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'Logged out'
        ]);
    }

    #[Route('/api/rooms', name: 'api_rooms', methods: ['GET'], priority: 100)]
    public function rooms(EntityManagerInterface $entityManager): Response
    {
        $rooms = $entityManager->getRepository(RoomListing::class)->findBy(['isBlocked' => false]);

        $payload = array_map(static function (RoomListing $room): array {
            return [
                'id' => $room->getId(),
                'number' => $room->getNumber(),
                'category' => $room->getCategory(),
                'description' => $room->getDescription(),
                'capacity' => $room->getCapacity(),
                'pricePerNight' => $room->getPricePerNight(),
                'isAvailable' => $room->isAvailable(),
                'location' => $room->getLocation(),
                'isBlocked' => $room->isBlocked(),
            ];
        }, $rooms);

        return new JsonResponse([
            'success' => true,
            'data' => $payload,
        ]);
    }

    #[Route('/api/bookings', name: 'api_bookings', methods: ['GET'], priority: 100)]
    public function bookings(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->resolveAuthenticatedUser($request, $entityManager);

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $bookings = $entityManager->getRepository(Booking::class)->findBy(['user' => $user], ['id' => 'DESC']);

        $payload = array_map(static function (Booking $booking): array {
            return [
                'id' => $booking->getId(),
                'roomId' => $booking->getRoom()?->getId(),
                'startDate' => $booking->getStartDate()?->format('Y-m-d'),
                'endDate' => $booking->getEndDate()?->format('Y-m-d'),
                'status' => $booking->getStatus(),
                'bookingType' => $booking->getBookingType(),
            ];
        }, $bookings);

        return new JsonResponse([
            'success' => true,
            'data' => $payload,
        ]);
    }

    #[Route('/api/bookings', name: 'api_bookings_create', methods: ['POST'], priority: 100)]
    public function createBooking(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->resolveAuthenticatedUser($request, $entityManager);

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['roomId'], $data['startDate'], $data['endDate'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'roomId, startDate and endDate are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $room = $entityManager->getRepository(RoomListing::class)->find($data['roomId']);
        if (!$room || $room->isBlocked() || !$room->isAvailable()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Room is not available'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startDate = new \DateTimeImmutable((string) $data['startDate']);
            $endDate = new \DateTimeImmutable((string) $data['endDate']);
        } catch (\Exception) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Invalid date format'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($endDate <= $startDate) {
            return new JsonResponse([
                'success' => false,
                'error' => 'endDate must be after startDate'
            ], Response::HTTP_BAD_REQUEST);
        }

        $hasConflict = $entityManager->getRepository(Booking::class)
            ->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.room = :room')
            ->andWhere('b.startDate < :endDate')
            ->andWhere('b.endDate > :startDate')
            ->setParameter('room', $room)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        if ((int) $hasConflict > 0) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Room is already booked for the selected dates'
            ], Response::HTTP_CONFLICT);
        }

        $booking = new Booking();
        $booking->setRoom($room);
        $booking->setUser($user);
        $booking->setStartDate($startDate);
        $booking->setEndDate($endDate);
        $booking->setStatus('pending');

        $entityManager->persist($booking);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $booking->getId(),
                'roomId' => $room->getId(),
                'userId' => $user->getId(),
                'startDate' => $booking->getStartDate()?->format('Y-m-d'),
                'endDate' => $booking->getEndDate()?->format('Y-m-d'),
                'status' => $booking->getStatus(),
            ]
        ], Response::HTTP_CREATED);
    }

    private function resolveAuthenticatedUser(Request $request, EntityManagerInterface $entityManager): ?LogInUsers
    {
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = str_replace('Bearer ', '', $authHeader);
            $decoded = base64_decode($token, true);

            if ($decoded && str_contains($decoded, ':')) {
                [$userId] = explode(':', $decoded, 2);
                $user = $entityManager->getRepository(LogInUsers::class)->find((int) $userId);
                if ($user) {
                    return $user;
                }
            }
        }

        if ($request->hasSession()) {
            $sessionUserId = $request->getSession()->get('api_user_id');
            if ($sessionUserId) {
                $user = $entityManager->getRepository(LogInUsers::class)->find((int) $sessionUserId);
                if ($user) {
                    return $user;
                }
            }
        }

        return null;
    }
}