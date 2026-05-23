<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\LogInUsers;
use App\Entity\UserActivity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
    )
    {
    }

    /**
     * Log an activity.
     *
     * @param string $action Short action code (e.g. 'room.created')
     * @param mixed $actor User entity or null
     * @param mixed $target Target entity or null
     * @param array $details Optional details
     */
    public function log(string $action, $actor = null, $target = null, array $details = []): void
    {
        $log = new ActivityLog();
        $log->setAction($action);

        if ($actor !== null) {
            $log->setActorType(get_class($actor));
            if (method_exists($actor, 'getId')) {
                $log->setActorId($actor->getId());
            }
            if (method_exists($actor, 'getEmail')) {
                $log->setActorEmail($actor->getEmail());
            }
        } else {
            $log->setActorType('system');
        }

        if ($target !== null) {
            $log->setTargetType(get_class($target));
            if (method_exists($target, 'getId')) {
                $log->setTargetId($target->getId());
            }
        }

        $log->setDetails($details ?: null);

        $this->em->persist($log);

        // Mirror to user_activity so booking and other user-driven actions appear
        // on the admin user activity page.
        if ($actor instanceof LogInUsers) {
            $managedUser = $this->em->find(LogInUsers::class, $actor->getId());

            if ($managedUser) {
                $request = $this->requestStack->getCurrentRequest();

                $userActivity = new UserActivity();
                $userActivity->setUser($managedUser);
                $userActivity->setAction(substr(strtolower($action), 0, 32));
                $userActivity->setIp($request?->getClientIp());
                $userActivity->setUserAgent($request?->headers->get('User-Agent'));

                $this->em->persist($userActivity);
            }
        }

        $this->em->flush();
    }
}
