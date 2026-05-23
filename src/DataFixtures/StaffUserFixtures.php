<?php

namespace App\DataFixtures;

use App\Entity\LogInUsers;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class StaffUserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Admin user
        $existing = $manager->getRepository(LogInUsers::class)->findOneBy(['email' => 'admin@staygrid.com']);
        if (!$existing) {
            $admin = new LogInUsers();
            $admin->setEmail('admin@staygrid.com');
            $admin->setRoles(['ROLE_ADMIN']);
            $hashed = $this->passwordHasher->hashPassword($admin, 'superuser');
            $admin->setPassword($hashed);
            $manager->persist($admin);
        }

        // Staff user
        $existing = $manager->getRepository(LogInUsers::class)->findOneBy(['email' => 'staff@gmail.com']);
        if (!$existing) {
            $staff = new LogInUsers();
            $staff->setEmail('staff@gmail.com');
            $staff->setRoles([LogInUsers::ROLE_STAFF]);
            $hashed = $this->passwordHasher->hashPassword($staff, 'superuser');
            $staff->setPassword($hashed);
            $manager->persist($staff);
        }

        $manager->flush();
    }
}
