<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RoomsController extends AbstractController
{
    #[Route('/rooms', name: 'app_rooms')]
    public function rooms(): Response
    {
        $rooms = [
            [
                'name' => 'Studio Deluxe',
                'description' => 'Perfect for solo travelers or couples.',
                'image' => 'images/studiosuite.png',
                'route' => 'app_room_studio_deluxe'
            ],
            [
                'name' => 'Executive Suite',
                'description' => 'Spacious stay with a full kitchen.',
                'image' => 'images/executivesuite.png',
                'route' => 'app_room_executive_suite'
            ],
            [
                'name' => 'Family Apartment',
                'description' => 'Multiple rooms ideal for groups.',
                'image' => 'images/familyapartment.png',
                'route' => 'app_room_family_apartment'
            ],
        ];

        return $this->render('vrooms/index.html.twig', [
            'rooms' => $rooms
        ]);
    }

    #[Route('/rooms/studio-deluxe', name: 'app_room_studio_deluxe')]
    public function studioDeluxe(): Response
    {
        return $this->render('vrooms/studio_deluxe.html.twig', [
            'title' => 'Studio Deluxe - StayGrid',
            'description' => 'Perfect for solo travelers or couples.',
            'image' => 'images/studiosuite.png'
        ]);
    }

    #[Route('/rooms/executive-suite', name: 'app_room_executive_suite')]
    public function executiveSuite(): Response
    {
        return $this->render('vrooms/executive_suite.html.twig', [
            'title' => 'Executive Suite - StayGrid',
            'description' => 'Spacious stay with a full kitchen.',
            'image' => 'images/executivesuite.png'
        ]);
    }

    #[Route('/rooms/family-apartment', name: 'app_room_family_apartment')]
    public function familyApartment(): Response
    {
        return $this->render('vrooms/family_apartment.html.twig', [
            'title' => 'Family Apartment - StayGrid',
            'description' => 'Multiple rooms ideal for groups.',
            'image' => 'images/familyapartment.png'
        ]);
    }
}
