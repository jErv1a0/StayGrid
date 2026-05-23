<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Entity\LogInUsers;
use App\Entity\UserActivity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class UserActivitySubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private ?LoggerInterface $logger;
    private EntityManagerInterface $em;
    
    /**
     * Track users logged in during this request cycle to prevent duplicate logging
     * @var array<int, true>
     */
    private array $loggedInUsersThisCycle = [];

    public function __construct(
        EntityManagerInterface $em,
        RequestStack $requestStack,
        ?LoggerInterface $logger = null
    ) {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Symfony 5.4+: Form login and some authenticators dispatch this
            LoginSuccessEvent::class => ['onLoginSuccess', 10],
            // Fallback for older authenticators that use InteractiveLoginEvent
            InteractiveLoginEvent::class => ['onLogin', 5],
            // Listen to logout
            LogoutEvent::class => 'onLogout',
            // Clear the cycle tracking at the end of the request
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getPassport()->getUser();
        
        if ($user instanceof LogInUsers) {
            // Mark this user as having been logged - prevents duplicate logging from InteractiveLoginEvent
            $this->loggedInUsersThisCycle[(int)$user->getId()] = true;
            $this->logActivity($user, 'login');
        }
    }

    /**
     * Fallback handler for InteractiveLoginEvent (older authenticators)
     * Only logs if we haven't already logged via LoginSuccessEvent
     */
    public function onLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!($user instanceof LogInUsers)) {
            return;
        }

        // Skip if we already logged this via LoginSuccessEvent (to avoid duplicates)
        if (isset($this->loggedInUsersThisCycle[(int)$user->getId()])) {
            return;
        }

        $this->logActivity($user, 'login');
    }

    public function onLogout(LogoutEvent $event): void
    {
        // Get the user from the token before the token is destroyed
        $user = $event->getToken()?->getUser();
        
        if ($user instanceof LogInUsers) {
            $this->logActivity($user, 'logout');
        }
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        // Clear the cycle tracking at the end of the request
        $this->loggedInUsersThisCycle = [];
    }

    /**
     * Creates and persists a UserActivity log record.
     */
    private function logActivity(LogInUsers $user, string $action): void
    {
        // Re-fetch the user to ensure it's a managed entity
        $managedUser = $this->em->find(LogInUsers::class, $user->getId());

        if (!$managedUser) {
            $this->logger?->warning('UserActivitySubscriber: Could not resolve managed user for logging.', ['email' => $user->getUserIdentifier()]);
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        
        $activity = new UserActivity();
        $activity->setUser($managedUser);
        $activity->setAction($action);
        
        if ($request) {
            $activity->setIp($request->getClientIp());
            $activity->setUserAgent($request->headers->get('User-Agent'));
        }

        try {
            $this->em->persist($activity);

            // Mirror to ActivityLog so admin "User & Staff Activity Log" page shows it.
            $activityLog = new ActivityLog();
            $activityLog->setAction(strtolower($action));
            $activityLog->setActorType(LogInUsers::class);
            $activityLog->setActorId($managedUser->getId());
            $activityLog->setActorEmail($managedUser->getEmail());
            $activityLog->setDetails([
                'ip' => $request?->getClientIp(),
                'user_agent' => $request?->headers->get('User-Agent'),
                'source' => 'user_activity',
            ]);

            $this->em->persist($activityLog);
            $this->em->flush();

            $this->logger?->info(sprintf('User activity logged: %s for user %s', $action, $managedUser->getEmail()), ['userId' => $managedUser->getId(), 'ip' => $request?->getClientIp()]);
        } catch (\Throwable $e) {
            $this->logger?->error('Failed to persist user activity log: ' . $e->getMessage());
        }
    }
}