<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppCustomAutheticatorAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_username', '');
        $password = $request->request->get('_password', '');
        $csrfToken = $request->request->get('_csrf_token');

        $request->getSession()->set(
            \Symfony\Component\Security\Http\SecurityRequestAttributes::LAST_USERNAME,
            $email
        );

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Prefer role-based dash redirect for admin/staff users; if neither, return to intended target path
        // 1️⃣ Determine roles first
        $user = $token->getUser();
        $roles = [];
        if (is_object($user) && method_exists($user, 'getRoles')) {
            $roles = $user->getRoles();
        } elseif (method_exists($token, 'getRoleNames')) {
            $roles = $token->getRoleNames();
        }
        // 2️⃣ Redirect by highest-priority role
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_home'));
        }

        if (in_array('ROLE_STAFF', $roles, true)) {
            try {
                $target = $this->urlGenerator->generate('app_staff_dashboard');
            } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException $e) {
                // Fallback to landing page if staff dashboard route is missing
                $target = $this->urlGenerator->generate('app_landing');
                @trigger_error('Route "app_staff_dashboard" not found; falling back to app_landing', E_USER_WARNING);
            }

            $response = new RedirectResponse($target);
            // Clear any admin remember-me cookie (prevents accidental admin auto-login from old cookie)
            $response->headers->clearCookie('_admin_remember_me', '/admin');
            return $response;
        }

        // 3️⃣ If neither admin nor staff, return to the intended target path (if any)
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Default: regular client dashboard
        return new RedirectResponse($this->urlGenerator->generate('app_user_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
