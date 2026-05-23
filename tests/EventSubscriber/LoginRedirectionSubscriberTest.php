<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\LoginRedirectionSubscriber;
use App\Entity\LogInUsers;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LoginRedirectionSubscriberTest extends TestCase
{
    public function testStaffIsRedirectedToStaffDashboard(): void
    {
        // Prepare a staff user
        $user = new LogInUsers();
        $user->setEmail('staff@example.com');
        $user->setRoles([LogInUsers::ROLE_STAFF]);

        // Use a typed Passport mock that returns our user
        $passport = $this->createMock(\Symfony\Component\Security\Http\Authenticator\Passport\Passport::class);
        $passport->method('getUser')->willReturn($user);

        // Event mock
        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getPassport')->willReturn($passport);

        // Url generator returns distinct urls for routes
        $urlGen = $this->createMock(UrlGeneratorInterface::class);
        $urlGen->method('generate')->willReturnMap([
            ['app_admin_user_activity_index', [], '/admin/activity'],
            ['app_staff_dashboard', [], '/staff/dashboard'],
            ['app_user_dashboard', [], '/user/dashboard'],
            ['app_landing', [], '/landing'],
        ]);

        $subscriber = new LoginRedirectionSubscriber($urlGen);

        // Expect setResponse to be called with a redirect to /staff/dashboard
        $event->expects($this->once())->method('setResponse')->with($this->callback(function ($response) {
            return $response instanceof RedirectResponse && $response->getTargetUrl() === '/staff/dashboard';
        }));

        $subscriber->onLoginSuccess($event);
    }

    public function testStaffFallbacksWhenStaffRouteMissing(): void
    {
        $user = new LogInUsers();
        $user->setEmail('staff@example.com');
        $user->setRoles([LogInUsers::ROLE_STAFF]);

        $passport = $this->createMock(\Symfony\Component\Security\Http\Authenticator\Passport\Passport::class);
        $passport->method('getUser')->willReturn($user);

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getPassport')->willReturn($passport);

        // UrlGenerator throws for app_staff_dashboard and returns landing for fallback
        $urlGen = $this->createMock(UrlGeneratorInterface::class);
        $urlGen->method('generate')->willReturnCallback(function (string $name) {
            if ($name === 'app_staff_dashboard') {
                throw new \Symfony\Component\Routing\Exception\RouteNotFoundException();
            }

            return '/landing';
        });

        $subscriber = new LoginRedirectionSubscriber($urlGen);

        $event->expects($this->once())->method('setResponse')->with($this->callback(function ($response) {
            return $response instanceof RedirectResponse && $response->getTargetUrl() === '/landing';
        }));

        $subscriber->onLoginSuccess($event);
    }
}
