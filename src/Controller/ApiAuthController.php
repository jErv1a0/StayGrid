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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class ApiAuthController extends AbstractController
{
    private const MOBILE_TOKEN_TTL_SECONDS = 604800;

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
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
    #[Route('/api/auth/verify-email', name: 'api_auth_verify_email', methods: ['GET'])]
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
    #[Route('/api/auth/login', name: 'api_auth_login_info', methods: ['GET'])]
    public function loginInfo(): Response
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'Use POST /api/auth/login with JSON body: {"email":"...","password":"..."} and send the returned access_token as Authorization: Bearer <token>'
        ]);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
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

        $authToken = $this->createBearerToken($user);

        // Keep a session-based fallback for mobile clients that reuse cookies.
        if ($request->hasSession()) {
            $request->getSession()->set('api_user_id', $user->getId());
        }

        return new JsonResponse([
            'success' => true,
            'token_type' => 'Bearer',
            'access_token' => $authToken['token'],
            'expires_in' => $authToken['expires_in'],
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName()
            ]
        ]);
    }

    #[Route('/api/auth/google/mobile', name: 'api_auth_google_mobile_info', methods: ['GET'])]
    public function googleMobileLoginInfo(): Response
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'Use POST /api/auth/google/mobile with JSON body {"token":"<google id token>"}',
        ]);
    }

    #[Route('/api/auth/google/mobile', name: 'api_auth_google_mobile', methods: ['POST'])]
    public function googleMobileLogin(
        Request $request,
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ): Response {
        $data = json_decode($request->getContent(), true);
        $idToken = is_array($data) ? ($data['token'] ?? null) : null;

        if (!is_string($idToken) || $idToken === '') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Missing Google token',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $response = $httpClient->request('GET', 'https://oauth2.googleapis.com/tokeninfo', [
                'query' => ['id_token' => $idToken],
                'timeout' => 5,
            ]);

            $googleData = $response->toArray(false);
        } catch (\Throwable $throwable) {
            $logger->error('Google token verification failed', [
                'error' => $throwable->getMessage(),
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to verify Google token',
            ], Response::HTTP_BAD_GATEWAY);
        }

        $googleClientId = (string) ($_ENV['GOOGLE_CLIENT_ID'] ?? $_SERVER['GOOGLE_CLIENT_ID'] ?? '');
        if ($googleClientId === '' || ($googleData['aud'] ?? null) !== $googleClientId) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Token audience mismatch',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (($googleData['email_verified'] ?? 'false') !== 'true') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Google email is not verified',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $email = mb_strtolower(trim((string) ($googleData['email'] ?? '')));
        if ($email === '') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Google token did not contain an email address',
            ], Response::HTTP_BAD_REQUEST);
        }

        $userRepository = $entityManager->getRepository(LogInUsers::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new LogInUsers();
            $user->setEmail($email);
            $user->setRoles([LogInUsers::ROLE_CLIENT]);
            $user->setIsVerified(true);

            $displayName = trim((string) ($googleData['name'] ?? ''));
            if ($displayName === '') {
                $displayName = trim((string) (($googleData['given_name'] ?? '') . ' ' . ($googleData['family_name'] ?? '')));
            }

            if ($displayName !== '') {
                $user->setFullName($displayName);
            }

            $randomPassword = bin2hex(random_bytes(16));
            $user->setPassword($passwordHasher->hashPassword($user, $randomPassword));

            $entityManager->persist($user);
            $entityManager->flush();
        } else {
            $updated = false;

            if (!$user->isVerified()) {
                $user->setIsVerified(true);
                $updated = true;
            }

            if (!$user->getFullName()) {
                $displayName = trim((string) ($googleData['name'] ?? ''));
                if ($displayName === '') {
                    $displayName = trim((string) (($googleData['given_name'] ?? '') . ' ' . ($googleData['family_name'] ?? '')));
                }

                if ($displayName !== '') {
                    $user->setFullName($displayName);
                    $updated = true;
                }
            }

            if ($updated) {
                $entityManager->flush();
            }
        }

        $authToken = $this->createBearerToken($user);

        if ($request->hasSession()) {
            $request->getSession()->set('api_user_id', $user->getId());
        }

        return new JsonResponse([
            'success' => true,
            'token_type' => 'Bearer',
            'access_token' => $authToken['token'],
            'expires_in' => $authToken['expires_in'],
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'isVerified' => $user->isVerified(),
            ],
        ]);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'])]
    #[Route('/api/user/profile', name: 'api_user_profile', methods: ['GET'])]
    public function me(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->resolveAuthenticatedUser($request, $entityManager);

        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
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
    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): Response
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'Logged out. Discard the bearer token on the client.'
        ]);
    }

    #[Route('/api/rooms', name: 'api_rooms', methods: ['GET'], priority: 100)]
    public function rooms(Request $request, EntityManagerInterface $entityManager): Response
    {
        $rooms = $entityManager->getRepository(RoomListing::class)->findBy(['isBlocked' => false]);

        $baseUrl = $request->getSchemeAndHttpHost();
        $payload = array_map(static function (RoomListing $room) use ($baseUrl): array {
            $imagePath = $room->getImagePath();
            $imageUrl = null;

            if ($imagePath) {
                $imageUrl = rtrim($baseUrl, '/') . '/' . ltrim($imagePath, '/');
            }

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
                'imagePath' => $imagePath,
                'imageUrl' => $imageUrl,
            ];
        }, $rooms);

        return $this->json([
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
        $payload = $this->decodeBearerToken($request);

        if ($payload && isset($payload['sub'])) {
            $user = $entityManager->getRepository(LogInUsers::class)->find((int) $payload['sub']);
            if ($user) {
                return $user;
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

    private function createBearerToken(LogInUsers $user): array
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + self::MOBILE_TOKEN_TTL_SECONDS;

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => 'staygrid-api',
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'jti' => bin2hex(random_bytes(16)),
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, (string) $this->getParameter('kernel.secret'), true));

        return [
            'token' => $encodedHeader . '.' . $encodedPayload . '.' . $signature,
            'expires_in' => self::MOBILE_TOKEN_TTL_SECONDS,
        ];
    }

    private function decodeBearerToken(Request $request): ?array
    {
        $authHeader = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return null;
        }

        return $this->validateToken((string) $matches[1]);
    }

    private function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $signature] = $parts;

        $headerJson = $this->base64UrlDecode($encodedHeader);
        $payloadJson = $this->base64UrlDecode($encodedPayload);

        if ($headerJson === null || $payloadJson === null) {
            return null;
        }

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (!is_array($header) || !is_array($payload)) {
            return null;
        }

        if (($header['alg'] ?? null) !== 'HS256' || ($header['typ'] ?? null) !== 'JWT') {
            return null;
        }

        if (($payload['iss'] ?? null) !== 'staygrid-api') {
            return null;
        }

        if (!isset($payload['exp']) || !is_int($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, (string) $this->getParameter('kernel.secret'), true));

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        return $payload;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}