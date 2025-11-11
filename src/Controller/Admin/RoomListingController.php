<?php

namespace App\Controller\Admin;

use App\Entity\RoomListing;
use App\Entity\Booking;
use App\Form\RoomListingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/roomlisting', name: 'app_admin_roomlisting_')]
class RoomListingController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $rooms = $em->getRepository(RoomListing::class)->findAll();

        $roomData = [];
        foreach ($rooms as $room) {
            $bookings = $em->getRepository(Booking::class)->findBy(
                ['room' => $room],
                ['startDate' => 'DESC']
            );

            $currentBooking = null;
            $durationDays = null;
            $durationHours = null;

            foreach ($bookings as $booking) {
                if ($booking->getStatus() === 'confirmed') {
                    $currentBooking = $booking;

                    if ($booking->getStartDate() && $booking->getEndDate()) {
                        $interval = $booking->getStartDate()->diff($booking->getEndDate());
                        $durationDays = $interval->days;
                        $durationHours = ($interval->days * 24) + $interval->h;
                    }
                    break;
                }
            }

            $roomData[] = [
                'room' => $room,
                'currentBooking' => $currentBooking,
                'durationDays' => $durationDays,
                'durationHours' => $durationHours,
            ];
        }

        return $this->render('admin/roomlisting/index.html.twig', [
            'page_title' => 'Room Listings',
            'roomData' => $roomData,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $roomListing = new RoomListing();
        $form = $this->createForm(RoomListingType::class, $roomListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('rooms_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                }
                $roomListing->setImage($newFilename);
            }

            $entityManager->persist($roomListing);
            $entityManager->flush();

            $this->addFlash('success', 'Room listing created successfully!');
            return $this->redirectToRoute('app_admin_roomlisting_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/roomlisting/new.html.twig', [
            'room_listing' => $roomListing,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RoomListing $roomListing, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RoomListingType::class, $roomListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('rooms_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                }
                $roomListing->setImage($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Room listing updated successfully!');
            return $this->redirectToRoute('app_admin_roomlisting_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/roomlisting/edit.html.twig', [
            'room_listing' => $roomListing,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, RoomListing $roomListing, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $roomListing->getId(), $request->request->get('_token'))) {
            $entityManager->remove($roomListing);
            $entityManager->flush();
            $this->addFlash('success', 'Room listing deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_roomlisting_index', [], Response::HTTP_SEE_OTHER);
    }
}
