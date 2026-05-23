<?php

namespace App\Tests\Security;

use App\Security\AppCustomAutheticatorAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class AppCustomAuthenticatorTest extends TestCase
{
    public function testAuthenticatorFallsBackWhenStaffRouteMissing(): void
    {
        $urlGen = $this->createMock(UrlGeneratorInterface::class);
        $urlGen->method('generate')->willReturnCallback(function (string $name) {
            if ($name === 'app_staff_dashboard') {
                throw new \Symfony\Component\Routing\Exception\RouteNotFoundException();
            }

            return '/landing';
        });

        $auth = new AppCustomAutheticatorAuthenticator($urlGen);

        $user = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $user->method('getRoles')->willReturn(['ROLE_STAFF']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $auth->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertSame('/landing', $response->getTargetUrl());
    }
}
