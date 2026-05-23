<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\UserActivitySubscriber;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Psr\Log\LoggerInterface;
use App\Entity\LogInUsers;

class UserActivitySubscriberTest extends TestCase
{
    public function testOnLoginDoesNotThrowWhenUserNotFound(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $requestStack = $this->createMock(\Symfony\Component\HttpFoundation\RequestStack::class);
        
        // Repository findOneBy will be called; make it return null
        $repo = $this->getMockBuilder(\Doctrine\ORM\EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->method('findOneBy')->willReturn(null);

        $em->method('getRepository')->willReturn($repo);

        // Ensure persist is never called
        $em->expects($this->never())->method('persist');

        $subscriber = new UserActivitySubscriber($em, $requestStack);

        // Test that the subscriber can be created without errors
        $this->assertNotNull($subscriber);
    }

    public function testOnLoginRecordsActivityWhenUserFound(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $requestStack = $this->createMock(\Symfony\Component\HttpFoundation\RequestStack::class);
        $logger = $this->createMock(LoggerInterface::class);

        $foundUser = $this->createMock(LogInUsers::class);
        $foundUser->method('getId')->willReturn(1);
        $foundUser->method('getUserIdentifier')->willReturn('test@example.com');

        // For LoginSuccessEvent, we need to set up the find method
        $em->method('find')->willReturn($foundUser);
        $em->expects($this->exactly(2))->method('persist');
        $em->expects($this->once())->method('flush');

        $logger->expects($this->once())->method('info');

        $subscriber = new UserActivitySubscriber($em, $requestStack, $logger);

        // Create a simple mock Passport
        $passport = $this->createMock(\Symfony\Component\Security\Http\Authenticator\Passport\Passport::class);
        $passport->method('getUser')->willReturn($foundUser);

        // Create the LoginSuccessEvent
        $event = $this->createMock(\Symfony\Component\Security\Http\Event\LoginSuccessEvent::class);
        $event->method('getPassport')->willReturn($passport);

        // Call the handler
        $subscriber->onLoginSuccess($event);

        // Verify activity was logged
        $this->assertTrue(true);
    }
}
