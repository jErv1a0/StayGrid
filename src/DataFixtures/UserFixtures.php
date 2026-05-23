<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        echo "[UserFixtures] running\n";

        $user = new User();
        $user->setEmail('SuperUser@Staygird');
        // Give this user both the regular admin role and a higher-privilege super-admin role.
        $user->setRoles(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
        
        // Hash the password "superuser"
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'superuser');
        $user->setPassword($hashedPassword);

        $manager->persist($user);
        $manager->flush();
    }
}
