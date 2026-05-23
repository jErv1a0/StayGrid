<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StaffRoomListingDesignTest extends WebTestCase
{
    public function testRoomListingIndexRenders(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', '/staff/roomlisting/');
        $this->assertNotEquals(500, $client->getResponse()->getStatusCode(), 'Room listings index returned 500: ' . $client->getResponse()->getContent());
        $this->assertStringContainsString('Units.', $client->getResponse()->getContent());
    }

    public function testBookingsPageRenders(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', '/staff/roomlisting/bookings');
        $this->assertNotEquals(500, $client->getResponse()->getStatusCode(), 'Bookings page returned 500: ' . $client->getResponse()->getContent());
        $this->assertStringContainsString('Bookings.', $client->getResponse()->getContent());
    }
}
