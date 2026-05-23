<?php

namespace App\Controller;

use App\Repository\RoomListingRepository;
use App\Entity\RoomListing;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/listings')]
class RoomListingController extends AbstractController
{
    #[Route('/', name: 'app_roomlisting_index', methods: ['GET'])]
    public function index(RoomListingRepository $roomListingRepository): Response
    {
        $rooms = $roomListingRepository->findBy(
            ['isAvailable' => true],
            ['pricePerNight' => 'ASC']
        );

        $pageTitle = $this->getUser()
            ? 'Browse Available Rooms'
            : 'View Our Listings';

        return $this->render('user/room_listing/index.html.twig', [
            'rooms' => $rooms,
            'page_title' => $pageTitle,
        ]);
    }

    #[Route('/{id}', name: 'app_roomlisting_show', methods: ['GET'])]
    public function show(RoomListing $room): Response
    {
        if (!$room->isAvailable() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException(
                'The room is not available for booking.'
            );
        }

        return $this->render('user/room_listing/show.html.twig', [
            'room' => $room,
            'page_title' => $room->getTitle(),
        ]);
    }
}
