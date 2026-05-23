<?php

namespace App\Tests\Controller;

use App\Entity\ActivityLog;
use App\Entity\LogInUsers;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminActivityLogTest extends WebTestCase
{
    public function testAdminCreatingRoomIsLogged(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $container = static::getContainer();
        try {
            $em = $container->get('doctrine')->getManager();
            // ensure DB connection is available
            $em->getConnection()->connect();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available for integration test: ' . $e->getMessage());
            return;
        }

        // Ensure admin user exists
        $adminEmail = 'admin_activity_test@example.com';
        $admin = $em->getRepository(LogInUsers::class)->findOneBy(['email' => $adminEmail]);
        if (!$admin) {
            $admin = new LogInUsers();
            $admin->setEmail($adminEmail);
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setPassword('dummy');
            $em->persist($admin);
            $em->flush();
        }

        // Authenticate
        // Authenticate against the admin firewall explicitly
        $client->loginUser($admin, 'admin');

        // Visit the Create Room page and submit the form
        $crawler = $client->request('GET', '/admin/roomlisting/new');
        $this->assertResponseIsSuccessful();

        // Follow redirects automatically for the POST-submit flow
        $client->followRedirects(true);

        // Find the form that actually contains the number input (more robust than assuming the first form)
        $forms = $crawler->filter('form');
        $targetForm = null;
        for ($i = 0; $i < $forms->count(); $i++) {
            $candidate = $forms->eq($i);
            if ($candidate->filter('input[name$="[number]"], input[name="number"]')->count() > 0) {
                $targetForm = $candidate;
                break;
            }
        }

        $this->assertNotNull($targetForm, 'No form containing a number input was found on new room page');

        $form = $targetForm->form();
        // Use field keys as exposed by the form node
        if (isset($form['number'])) {
            $form['number'] = 'ACT-001';
            $form['pricePerNight'] = '1500';
            $form['capacity'] = '2';
            $form['description'] = 'Integration test room';
        } else {
            // fallback to using the full input names if necessary
            $numInput = $targetForm->filter('input[name$="[number]"]')->first();
            $this->assertGreaterThan(0, $numInput->count(), 'Number input not found in target form');
            $form[$numInput->attr('name')] = 'ACT-001';

            $form[$targetForm->filter('input[name$="[pricePerNight]"]')->first()->attr('name')] = '1500';
            $form[$targetForm->filter('input[name$="[capacity]"]')->first()->attr('name')] = '2';
            $form[$targetForm->filter('textarea[name$="[description]"]')->first()->attr('name')] = 'Integration test room';
        }

        $client->submit($form);
        // We follow redirects automatically above, so assert the final response is successful
        $this->assertResponseIsSuccessful();

        // Check the activity log
        $log = $em->getRepository(ActivityLog::class)->findOneBy(['action' => 'room.created']);
        $this->assertNotNull($log, 'Expected an activity log entry for room.created');
        $this->assertStringContainsString('ACT-001', json_encode($log->getDetails() ?? []));
        $this->assertEquals($adminEmail, $log->getActorEmail());
    }
}
