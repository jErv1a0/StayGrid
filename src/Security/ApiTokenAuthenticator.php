<?php

namespace App\Security;

use App\Repository\LogInUsersRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private LogInUsersRepository $userRepository, private string $kernelSecret)
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization') && str_starts_with((string)$request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $header = $request->headers->get('Authorization', '');
        $token = substr($header, 7);
        if (!$token) {
            throw new CustomUserMessageAuthenticationException('No token provided.');
        }

        $payload = $this->validateToken($token);
        if (!$payload || !is_array($payload)) {
            throw new CustomUserMessageAuthenticationException('Invalid token.');
        }

        if (isset($payload['exp']) && is_int($payload['exp']) && $payload['exp'] < time()) {
            throw new CustomUserMessageAuthenticationException('Token expired.');
        }

        // Prefer subject (sub) then id or email
        $userIdentifier = null;
        if (isset($payload['sub'])) {
            $userIdentifier = (int)$payload['sub'];
            $user = $this->userRepository->find($userIdentifier);
        } elseif (isset($payload['id'])) {
            $userIdentifier = (int)$payload['id'];
            $user = $this->userRepository->find($userIdentifier);
        } elseif (isset($payload['email'])) {
            $user = $this->userRepository->findOneBy(['email' => $payload['email']]);
        } else {
            $user = null;
        }

        return new SelfValidatingPassport(new UserBadge($user?->getEmail() ?? (string)$userIdentifier, fn($id) => $user));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => $exception->getMessageKey()], Response::HTTP_UNAUTHORIZED);
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

        if (($header['alg'] ?? null) !== 'HS256') {
            return null;
        }

        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->kernelSecret, true));

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
