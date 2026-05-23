<?php

namespace App\Tests\Controller\Api;

use App\Entity\Booking;
use App\Entity\LogInUsers;
use App\Entity\RoomListing;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MobileApiTest extends WebTestCase
{
    public function testMobileEndpointsReturnStandardizedJson(): void
    {
        if (!getenv('KERNEL_CLASS')) {
            $this->markTestSkipped('Kernel not available in this environment.');
        }

        $client = static::createClient();
        $container = static::getContainer();

        $em = $container->get('doctrine')->getManager();
        $this->ensureSqliteColumns($em->getConnection());
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'mobile-api-test@example.com';
        $plainPassword = 'StrongPass123!';

        $existing = $em->getRepository(LogInUsers::class)->findOneBy(['email' => $email]);
        if ($existing) {
            foreach ($existing->getBookings() as $booking) {
                $em->remove($booking);
            }
            $em->remove($existing);
            $em->flush();
        }

        $user = new LogInUsers();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $plainPassword));
        $user->setRoles(['ROLE_CLIENT']);
        $user->setIsVerified(true);
        $user->setFullName('Mobile API User');

        $room = new RoomListing();
        $room->setNumber('API-1');
        $room->setCategory('Waitlist');
        $room->setDescription('A room created in functional test');
        $room->setCapacity(2);
        $room->setPricePerNight(120.00);
        $room->setIsAvailable(true);
        $room->setIsBlocked(false);
        $room->setLocation('Test API Zone');

        $em->persist($user);
        $em->persist($room);
        $em->flush();

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $plainPassword])
        );

        $status = $client->getResponse()->getStatusCode();
        $content = $client->getResponse()->getContent();
        
        if ($status !== 200) {
            echo "Login failed with status $status: $content\n";
        }
        
        $this->assertSame(200, $status);
        $loginData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('success', $loginData);
        $this->assertTrue($loginData['success']);

        $client->request('GET', '/api/rooms');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $roomsData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $roomsData);
        $this->assertNotEmpty($roomsData['data']);
        $roomIds = array_map(static fn (array $roomRow): mixed => $roomRow['id'] ?? null, $roomsData['data']);
        $this->assertContains($room->getId(), $roomIds);

        $client->request('GET', '/api/bookings');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $bookingsData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $bookingsData);
        $this->assertSame(0, count($bookingsData['data']));

        $today = new \DateTimeImmutable('tomorrow');
        $tomorrow = $today->modify('+1 day');

        $client->request(
            'POST',
            '/api/bookings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'roomId' => $room->getId(),
                'startDate' => $today->format('Y-m-d'),
                'endDate' => $tomorrow->format('Y-m-d'),
            ])
        );

        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $newBookingData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('success', $newBookingData);
        $this->assertTrue($newBookingData['success']);
        $this->assertArrayHasKey('data', $newBookingData);

        $client->request('GET', '/api/bookings');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $bookingsData = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, count($bookingsData['data']));

        // cleanup
        foreach ($em->getRepository(Booking::class)->findBy(['user' => $user]) as $booking) {
            $em->remove($booking);
        }
        $em->remove($room);
        $em->remove($user);
        $em->flush();
    }

    private function ensureSqliteColumns(Connection $connection): void
    {
        if ($connection->getDatabasePlatform()->getName() !== 'sqlite') {
            return;
        }

        $schemaManager = $connection->createSchemaManager();

        $bookingColumns = $schemaManager->listTableColumns('booking');
        if (!isset($bookingColumns['booking_type'])) {
            $connection->executeStatement("ALTER TABLE booking ADD COLUMN booking_type VARCHAR(20) NOT NULL DEFAULT 'daily'");
        }
        if (!isset($bookingColumns['number_of_hours'])) {
            $connection->executeStatement('ALTER TABLE booking ADD COLUMN number_of_hours SMALLINT DEFAULT NULL');
        }

        $roomColumns = $schemaManager->listTableColumns('roomlisting');
        if (!isset($roomColumns['price_per_hour'])) {
            $connection->executeStatement('ALTER TABLE roomlisting ADD COLUMN price_per_hour NUMERIC(10, 2) DEFAULT NULL');
        }
    }
}
