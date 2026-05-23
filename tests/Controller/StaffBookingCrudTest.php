<?php

namespace App\Tests\Controller;

use App\Entity\Booking;
use App\Entity\LogInUsers;
use App\Entity\RoomListing;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StaffBookingCrudTest extends WebTestCase
{
    public function testStaffCanViewEditAndDeleteBooking(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        // Ensure there's a staff user
        $staff = $em->getRepository(LogInUsers::class)->findOneBy(['email' => 'staff@gmail.com']);
        if (!$staff) {
            $staff = new LogInUsers();
            $staff->setEmail('staff@gmail.com');
            $staff->setRoles([LogInUsers::ROLE_STAFF]);
            $staff->setPassword('dummy');
            $em->persist($staff);
            $em->flush();
        }

        // Ensure there's a room for the booking
        $room = $em->getRepository(RoomListing::class)->findOneBy([]);
        if (!$room) {
            $this->markTestSkipped('No RoomListing available to attach booking.');
        }

        // Create booking
        $booking = new Booking();
        $booking->setRoom($room);
        $booking->setStartDate(new \DateTimeImmutable('+1 day'));
        $booking->setEndDate(new \DateTimeImmutable('+2 days'));
        $booking->setStatus('confirmed');
        $booking->setUser($staff);

        $em->persist($booking);
        $em->flush();

        // Authenticate as staff
        $client->loginUser($staff);

        // View
        $client->request('GET', '/staff/bookings/'.$booking->getId());
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('File', $client->getResponse()->getContent());

        // Edit
        $crawler = $client->request('GET', '/staff/bookings/'.$booking->getId().'/edit');
        $this->assertResponseIsSuccessful();

        $editToken = $crawler->filter('input[name="booking[_token]"]')->attr('value');
        $client->request('POST', '/staff/bookings/'.$booking->getId().'/edit', [
            'booking' => [
                'startDate' => (new \DateTimeImmutable('+3 days'))->format('Y-m-d'),
                'endDate' => (new \DateTimeImmutable('+4 days'))->format('Y-m-d'),
                'status' => 'cancelled',
                'bookingType' => 'daily',
                '_token' => $editToken,
            ],
        ]);
        $this->assertResponseRedirects('/staff/roomlisting/bookings');

        // Delete
        $deletePage = $client->request('GET', '/staff/bookings/'.$booking->getId());
        $deleteToken = $deletePage->filter('form[action="/staff/bookings/'.$booking->getId().'/delete"] input[name="_token"]')->attr('value');
        $client->request('POST', '/staff/bookings/'.$booking->getId().'/delete', ['_token' => $deleteToken]);
        $this->assertResponseRedirects('/staff/roomlisting/bookings');

        // Confirm deletion
        $deleted = $em->getRepository(Booking::class)->find($booking->getId());
        $this->assertNull($deleted);
    }
}
