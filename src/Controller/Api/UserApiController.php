<?php

namespace App\Controller\Api;

use App\Entity\LogInUsers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/user')]
class UserApiController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/profile', name: 'api_user_profile_get', methods: ['GET'])]
    public function profile(Request $request): JsonResponse
    {
        /** @var LogInUsers|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $fullName = $user->getFullName() ?? '';
        $first = null;
        $last = null;
        if ($fullName !== '') {
            $parts = preg_split('/\s+/', trim($fullName));
            $first = $parts[0] ?? null;
            $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null;
        }

        $avatar = $user->getProfilePicture();
        $avatarUrl = null;
        if ($avatar) {
            $avatarUrl = $request->getSchemeAndHttpHost() . '/uploads/profile_pictures/' . $avatar;
        }

        // created_at not present on entity; try getter if available
        $createdAt = null;
        if (method_exists($user, 'getCreatedAt')) {
            $ca = $user->getCreatedAt();
            if ($ca) {
                $createdAt = $ca instanceof \DateTimeInterface ? $ca->format(DATE_ATOM) : (string)$ca;
            }
        }

        $data = [
            'id' => $user->getId(),
            'first_name' => $first,
            'last_name' => $last,
            'email' => $user->getEmail(),
            'phone' => $user->getPhoneNumber(),
            'avatar_url' => $avatarUrl,
            'created_at' => $createdAt,
        ];

        return new JsonResponse($data);
    }

    #[Route('/profile', name: 'api_user_profile_put', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var LogInUsers|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $first = isset($payload['first_name']) ? trim((string)$payload['first_name']) : null;
        $last = isset($payload['last_name']) ? trim((string)$payload['last_name']) : null;
        $phone = isset($payload['phone']) ? trim((string)$payload['phone']) : null;

        // Reuse existing profile update logic: set fullName and phoneNumber
        $fullName = null;
        if ($first !== null && $last !== null) {
            $fullName = $first . ' ' . $last;
        } elseif ($first !== null) {
            $fullName = $first . ($user->getFullName() ? ' ' . $user->getFullName() : '');
        } elseif ($last !== null) {
            $fullName = ($user->getFullName() ? $user->getFullName() . ' ' : '') . $last;
        }

        if ($fullName !== null) {
            $user->setFullName($fullName);
        }

        if ($phone !== null) {
            $user->setPhoneNumber($phone);
        }

        $this->em->persist($user);
        $this->em->flush();

        // Prepare response similar to GET
        $fullName = $user->getFullName() ?? '';
        $parts = preg_split('/\s+/', trim($fullName));
        $first = $parts[0] ?? null;
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null;

        $avatar = $user->getProfilePicture();
        $avatarUrl = null;
        if ($avatar) {
            $avatarUrl = $request->getSchemeAndHttpHost() . '/uploads/profile_pictures/' . $avatar;
        }

        $createdAt = null;
        if (method_exists($user, 'getCreatedAt')) {
            $ca = $user->getCreatedAt();
            if ($ca) {
                $createdAt = $ca instanceof \DateTimeInterface ? $ca->format(DATE_ATOM) : (string)$ca;
            }
        }

        $data = [
            'id' => $user->getId(),
            'first_name' => $first,
            'last_name' => $last,
            'email' => $user->getEmail(),
            'phone' => $user->getPhoneNumber(),
            'avatar_url' => $avatarUrl,
            'created_at' => $createdAt,
        ];

        return new JsonResponse($data);
    }
}
