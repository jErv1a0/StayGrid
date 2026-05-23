<?php

namespace App\Tests\Controller\Security;

use App\Entity\LogInUsers;
use App\Entity\UserActivity;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserActivityFunctionalTest extends WebTestCase
{
    public function testLoginAndLogoutCreateActivityRecords(): void
    {
        if (!getenv('KERNEL_CLASS')) {
            $this->markTestSkipped('Kernel not available in this environment.');
        }

        $client = static::createClient();
        $container = static::getContainer();

        $em = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'functionaltest@example.com';
        $plain = 'p@ssword123';

        // Create user
        // Ensure no pre-existing user with the test email remains from prior runs
        $existing = $em->getRepository(LogInUsers::class)->findOneBy(['email' => $email]);
        if ($existing) {
            // remove prior test artifacts
            foreach ($em->getRepository(UserActivity::class)->findBy(['user' => $existing]) as $a) {
                $em->remove($a);
            }
            $em->remove($existing);
            $em->flush();
        }

        $user = new LogInUsers();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $plain));
        $user->setIsVerified(true);
        $em->persist($user);
        $em->flush();

        try {
            // Get CSRF token from login form
            $crawler = $client->request('GET', '/login');
            $csrf = $crawler->filter('input[name="_csrf_token"]')->attr('value');

            $client->followRedirects(true);
            $client->request('POST', '/login', [
                '_username' => $email,
                '_password' => $plain,
                '_csrf_token' => $csrf,
            ]);

            // Ensure a login activity was recorded
            $activityRepo = $em->getRepository(UserActivity::class);
            $loginActivities = $activityRepo->findBy(['user' => $user, 'action' => 'login']);
            $this->assertNotEmpty($loginActivities, 'Expected at least one login activity');

            // Trigger logout
            $client->request('GET', '/logout');

            $logoutActivities = $activityRepo->findBy(['user' => $user, 'action' => 'logout']);
            $this->assertNotEmpty($logoutActivities, 'Expected at least one logout activity');
        } finally {
            // Clean up - ensure we operate on managed entities
            $managedUser = $em->getRepository(LogInUsers::class)->findOneBy(['email' => $email]);

            if ($managedUser) {
                foreach ($em->getRepository(UserActivity::class)->findBy(['user' => $managedUser]) as $a) {
                    $em->remove($a);
                }
                $em->remove($managedUser);
                $em->flush();
            }
        }
    }
}
