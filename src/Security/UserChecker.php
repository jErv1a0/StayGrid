<?php

namespace App\Security;

use App\Entity\LogInUsers;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof LogInUsers) {
            return;
        }

        if (!$user->isVerified()) {
            // This exception message is shown to the user on the login page
            throw new CustomUserMessageAccountStatusException('Your account is not verified. Please check your email inbox.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}