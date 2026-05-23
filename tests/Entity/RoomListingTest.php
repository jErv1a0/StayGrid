<?php

namespace App\Tests\Entity;

use App\Entity\RoomListing;
use PHPUnit\Framework\TestCase;

class RoomListingTest extends TestCase
{
    public function testIsAvailableDefaultsToTrueWhenNull(): void
    {
        $room = new RoomListing();

        // Force the private property to null to simulate legacy DB state
        $ref = new \ReflectionProperty(RoomListing::class, 'isAvailable');
        $ref->setAccessible(true);
        $ref->setValue($room, null);

        $this->assertTrue($room->isAvailable());
    }

    public function testSetIsAvailableFalse(): void
    {
        $room = new RoomListing();
        $room->setIsAvailable(false);

        $this->assertFalse($room->isAvailable());
    }

    public function testGetTitleComposedFromCategoryAndNumber(): void
    {
        $room = new RoomListing();
        $room->setNumber('100A');
        $room->setCategory('Studio Deluxe');

        $this->assertEquals('Studio Deluxe — Room 100A', $room->getTitle());
    }

    public function testSetAndGetLocation(): void
    {
        $room = new RoomListing();
        $this->assertNull($room->getLocation());

        $room->setLocation('Makati, Metro Manila');
        $this->assertSame('Makati, Metro Manila', $room->getLocation());
    }
}
