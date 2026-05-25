<?php

namespace App\Security;

use App\Entity\LogInUsers;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly RouterInterface $router,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly string $profilePictureDirectory
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $client->setAsStateless();
        $accessToken = $this->fetchAccessToken($client);
        
        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUserFromToken($accessToken);
        $email = mb_strtolower(trim((string) $googleUser->getEmail()));

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($email) {
                $existingUser = $this->entityManager->getRepository(LogInUsers::class)->findOneBy(['email' => $email]);

                if ($existingUser) {
                    if (!$existingUser->isVerified()) {
                        $existingUser->setIsVerified(true);
                        $this->entityManager->flush();
                    }
                    return $existingUser;
                }

                $newUser = new LogInUsers();
                $newUser->setEmail($email);
                $newUser->setRoles([LogInUsers::ROLE_CLIENT]);
                $newUser->setIsVerified(true);

                $randomPassword = bin2hex(random_bytes(16));
                $newUser->setPassword($this->passwordHasher->hashPassword($newUser, $randomPassword));

                // If Google provided an avatar URL, try to download and save it
                $avatarUrl = $googleUser->getAvatar();
                if ($avatarUrl) {
                    try {
                        $imageContents = @file_get_contents($avatarUrl);
                        if ($imageContents !== false) {
                            $path = parse_url($avatarUrl, PHP_URL_PATH);
                            $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
                            $safeFilename = uniqid('google_', true) . '.' . $ext;
                            $target = rtrim($this->profilePictureDirectory, "\\/") . DIRECTORY_SEPARATOR . $safeFilename;
                            @file_put_contents($target, $imageContents);
                            $newUser->setProfilePicture($safeFilename);
                        }
                    } catch (\Throwable $e) {
                        // Don't break authentication if avatar download fails; continue silently
                    }
                }

                $this->entityManager->persist($newUser);
                $this->entityManager->flush();

                return $newUser;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        // Check if this is a new Google user (no password set yet)
        if ($user instanceof LogInUsers && empty($user->getPassword())) {
            // Redirect to completion form
            return new RedirectResponse($this->router->generate('app_register_google_complete'));
        }

        $roles = [];
        if (is_object($user) && method_exists($user, 'getRoles')) {
            $roles = $user->getRoles();
        } elseif (method_exists($token, 'getRoleNames')) {
            $roles = $token->getRoleNames();
        }

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->router->generate('app_admin_home'));
        }

        try {
            $target = $this->router->generate('app_staff_dashboard');
        } catch (\Exception $e) {
            $target = $this->router->generate('app_landing');
        }

        $response = new RedirectResponse($target);
        $response->headers->clearCookie('_admin_remember_me', '/admin');
        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}