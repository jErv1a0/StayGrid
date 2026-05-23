<?php

namespace App\DataFixtures;

use App\Entity\RoomListing;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $rooms = [
            [
                'number' => '101',
                'category' => 'Deluxe',
                'description' => 'Spacious deluxe room with ocean view',
                'capacity' => 2,
                'pricePerNight' => '150.00',
                'isAvailable' => true,
                'image' => 'executivefc.png',
                'location' => 'Floor 1',
            ],
            [
                'number' => '102',
                'category' => 'Standard',
                'description' => 'Comfortable standard room with city view',
                'capacity' => 2,
                'pricePerNight' => '120.00',
                'isAvailable' => true,
                'image' => 'studioMP.png',
                'location' => 'Floor 1',
            ],
            [
                'number' => '201',
                'category' => 'Suite',
                'description' => 'Luxurious suite with private balcony',
                'capacity' => 4,
                'pricePerNight' => '250.00',
                'isAvailable' => true,
                'image' => 'executivesuite.png',
                'location' => 'Floor 2',
            ],
            [
                'number' => '202',
                'category' => 'Standard',
                'description' => 'Standard room with garden view',
                'capacity' => 2,
                'pricePerNight' => '100.00',
                'isAvailable' => false,
                'image' => 'studiocityview.png',
                'location' => 'Floor 2',
            ],
            [
                'number' => '301',
                'category' => 'Economy',
                'description' => 'Budget-friendly economy room',
                'capacity' => 1,
                'pricePerNight' => '80.00',
                'isAvailable' => true,
                'image' => 'familyapartmentFH.png',
                'location' => 'Floor 3',
            ],
        ];

        foreach ($rooms as $roomData) {
            $room = new RoomListing();
            $room->setNumber($roomData['number']);
            $room->setCategory($roomData['category']);
            $room->setDescription($roomData['description']);
            $room->setCapacity($roomData['capacity']);
            $room->setPricePerNight($roomData['pricePerNight']);
            $room->setIsAvailable($roomData['isAvailable']);
            $room->setImage($roomData['image']);
            $room->setLocation($roomData['location']);
            $room->setIsBlocked(false);
            
            $manager->persist($room);
        }

        $manager->flush();
    }
}
