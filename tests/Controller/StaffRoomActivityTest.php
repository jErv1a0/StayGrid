<?php

namespace App\Tests\Controller;

use App\Entity\ActivityLog;
use App\Entity\LogInUsers;
use App\Entity\RoomListing;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StaffRoomActivityTest extends WebTestCase
{
    public function testStaffEditCreatesActivityLog(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $container = static::getContainer();
        try {
            $em = $container->get('doctrine')->getManager();
            $em->getConnection()->connect();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available for integration test: ' . $e->getMessage());
            return;
        }

        // Ensure staff user exists
        $email = 'staff_activity_test@example.com';
        $staff = $em->getRepository(LogInUsers::class)->findOneBy(['email' => $email]);
        if (!$staff) {
            $staff = new LogInUsers();
            $staff->setEmail($email);
            $staff->setRoles([LogInUsers::ROLE_STAFF]);
            $staff->setPassword('dummy');
            $em->persist($staff);
            $em->flush();
        }

        // Create a room owned by the system
        $room = new RoomListing();
        $room->setNumber('STAFF-ACT-01');
        $room->setPricePerNight(1000);
        $room->setCapacity(2);
        $em->persist($room);
        $em->flush();

        $client->loginUser($staff);
        $client->followRedirects(true);

        // Visit edit page and submit changed data
        $crawler = $client->request('GET', '/staff/roomlisting/'.$room->getId().'/edit');
        $this->assertResponseIsSuccessful();

        // Find the form that actually contains the 'number' input
        $forms = $crawler->filter('form');
        $targetForm = null;
        for ($i = 0; $i < $forms->count(); $i++) {
            $candidate = $forms->eq($i);
            if ($candidate->filter('input[name$="[number]"], input[name="number"]')->count() > 0) {
                $targetForm = $candidate;
                break;
            }
        }

        $this->assertNotNull($targetForm, 'No form containing a number input was found on edit page');

        $form = $targetForm->form();
        if (isset($form['number'])) {
            $form['number'] = 'STAFF-ACT-01-UPDATED';
        } else {
            $numInput = $targetForm->filter('input[name$="[number]"]')->first();
            if ($numInput->count() === 0) {
                $numInput = $targetForm->filter('input[name="number"]')->first();
            }

            $this->assertGreaterThan(0, $numInput->count(), 'Number input not found in any form');
            $inputName = $numInput->attr('name');
            $form[$inputName] = 'STAFF-ACT-01-UPDATED';
        }

        $client->submit($form);
        // We're following redirects automatically above, so assert that the final response is successful
        $this->assertResponseIsSuccessful();

        // Check activity log entry
        $log = $em->getRepository(ActivityLog::class)->findOneBy(['action' => 'room.updated']);
        $this->assertNotNull($log, 'Expected an activity log entry for room.updated');
        $this->assertEquals($email, $log->getActorEmail());
        $this->assertStringContainsString('STAFF-ACT-01', json_encode($log->getDetails() ?? []));
    }
}
