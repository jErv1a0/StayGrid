<?php

namespace App\Controller\User;

use App\Repository\RoomListingRepository; 
use App\Entity\RoomListing;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RoomsController extends AbstractController
{

    #[Route('/user/rooms', name: 'app_user_rooms_rooms')]
    public function rooms(RoomListingRepository $roomListingRepository): Response
    {

        $roomListings = $roomListingRepository->findAll();

        $featuredRooms = $roomListingRepository->findBy([], ['id' => 'DESC'], 5);

        return $this->render('user/Rooms/rooms.html.twig', [
            'roomListings' => $roomListings,
            'featuredRooms' => $featuredRooms,
        ]);
    }

    #[Route('/user/rooms/{id}', name: 'app_user_rooms_show', methods: ['GET'])]
    public function show(RoomListing $room): Response
    {
        if (!$room->isAvailable() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('The room is not available for booking.');
        }

        return $this->render('user/room_listing/show.html.twig', [
            'room' => $room,
            'page_title' => $room->getTitle(),
        ]);
    }
}