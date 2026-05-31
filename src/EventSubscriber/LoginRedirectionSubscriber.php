<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginRedirectionSubscriber implements EventSubscriberInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        // This event fires after successful login OR successful "Remember Me" re-authentication
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        if (str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $user = $event->getPassport()->getUser();

        // Ensure we are dealing with a UserInterface entity
        if (!$user instanceof UserInterface) {
            return;
        }

        $roles = $user->getRoles();
        $targetPath = '';

        // Priority: Admin > Staff > Client
        if (in_array('ROLE_ADMIN', $roles, true)) {
            // Redirect ADMINs to the Admin Dashboard (not directly to the user activity list)
            // This prevents accidental exposure of the user activity page when there is any role confusion.
            try {
                $targetPath = $this->urlGenerator->generate('app_admin_home');
            } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException $e) {
                $targetPath = $this->urlGenerator->generate('app_landing');
                @trigger_error('Route "app_admin_home" not found; Login redirection falling back to app_landing', E_USER_WARNING);
            }
        } elseif (in_array('ROLE_STAFF', $roles, true)) {
            // Redirect STAFF to the staff dashboard — but gracefully handle a missing route
            try {
                $targetPath = $this->urlGenerator->generate('app_staff_dashboard');
            } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException $e) {
                $targetPath = $this->urlGenerator->generate('app_landing');
                @trigger_error('Route "app_staff_dashboard" not found; Login redirection falling back to app_landing', E_USER_WARNING);
            }
        } elseif (in_array('ROLE_CLIENT', $roles, true)) {
            // Redirect standard clients to their user dashboard route
            try {
                $targetPath = $this->urlGenerator->generate('app_user_dashboard');
            } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException $e) {
                // Fallback if the user dashboard route doesn't exist yet
                $targetPath = $this->urlGenerator->generate('app_landing');
                @trigger_error('Route "app_user_dashboard" not found; Login redirection falling back to app_landing', E_USER_WARNING);
            }
        } else {
            // Fallback: If no specific role is matched, send to a safe default page
            $targetPath = $this->urlGenerator->generate('app_landing');
        }

        // Overwrite the default response Symfony generates with our role-specific path
        $response = new RedirectResponse($targetPath);
        $event->setResponse($response);
    }
}