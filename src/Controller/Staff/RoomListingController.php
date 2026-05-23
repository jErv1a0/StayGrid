<?php

namespace App\Controller\Staff;

use App\Entity\RoomListing;
use App\Entity\Booking;
use App\Form\RoomListingType;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/roomlisting', name: 'app_staff_roomlisting_')]
class RoomListingController extends AbstractController
{
    private \App\Service\ActivityLogger $activityLogger;

    public function __construct(\App\Service\ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $rooms = $em->getRepository(RoomListing::class)->findAll();

        return $this->render('staff/roomlisting/index.html.twig', [
            'page_title' => 'Staff Room Listings',
            'rooms' => $rooms,
        ]);
    }

    #[IsGranted('ROLE_STAFF')]
    #[Route('/new', name: 'new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $roomListing = new RoomListing();
        $form = $this->createForm(RoomListingType::class, $roomListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            // Prevent accidental duplicate submissions: if a room with same number+location
            // already exists, redirect to edit that existing room instead of creating a new one.
            $existing = $em->getRepository(RoomListing::class)->findOneBy([
                'number' => $roomListing->getNumber(),
                'location' => $roomListing->getLocation(),
            ]);

            if ($existing) {
                $this->addFlash('warning', 'A room with the same number and location already exists.');
                return $this->redirectToRoute('app_staff_roomlisting_edit', ['id' => $existing->getId()]);
            }

            if ($imageFile) {
                // Save images under public/uploads/rooms and persist a standardized path
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/rooms';
                    if (!is_dir($uploadsDir)) {
                        @mkdir($uploadsDir, 0777, true);
                    }
                    $imageFile->move($uploadsDir, $newFilename);
                    $roomListing->setImage('uploads/rooms/' . $newFilename);
                } catch (\Exception $e) {
                    // ignore upload errors for staff flow
                }
            }

            $em->persist($roomListing);
            $em->flush();

            // Log activity for staff-created room
            $this->activityLogger->log('room.created', $this->getUser(), $roomListing, ['number' => $roomListing->getNumber()]);

            $this->addFlash('success', 'Room listing created successfully.');
            return $this->redirectToRoute('app_staff_roomlisting_index');
        }

        return $this->render('staff/roomlisting/new.html.twig', [
            'page_title' => 'Add Room',
            'form' => $form->createView(),
            'room_listing' => $roomListing,
        ]);
    }

    #[IsGranted('ROLE_STAFF')]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(Request $request, RoomListing $roomListing, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RoomListingType::class, $roomListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/rooms';
                    if (!is_dir($uploadsDir)) {
                        @mkdir($uploadsDir, 0777, true);
                    }
                    $imageFile->move($uploadsDir, $newFilename);
                    $roomListing->setImage('uploads/rooms/' . $newFilename);
                } catch (\Exception $e) {
                    // ignore
                }
            }

            $em->flush();

            // Log activity for staff update
            $this->activityLogger->log('room.updated', $this->getUser(), $roomListing, ['number' => $roomListing->getNumber()]);

            $this->addFlash('success', 'Room listing updated.');
            return $this->redirectToRoute('app_staff_roomlisting_index');
        }

        return $this->render('staff/roomlisting/edit.html.twig', [
            'page_title' => 'Edit Room',
            'form' => $form->createView(),
            'room' => $roomListing,
        ]);
    }

    #[IsGranted('ROLE_STAFF')]
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, RoomListing $roomListing, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $roomListing->getId(), $request->request->get('_token'))) {
            // Log before removal
            $this->activityLogger->log('room.deleted', $this->getUser(), $roomListing, ['number' => $roomListing->getNumber()]);

            $em->remove($roomListing);
            $em->flush();
            $this->addFlash('success', 'Room listing deleted.');
        }

        return $this->redirectToRoute('app_staff_roomlisting_index');
    }

    #[Route('/bookings', name: 'bookings', methods: ['GET'])]
    public function bookings(BookingRepository $bookingRepository): Response
    {
        $allBookings = $bookingRepository->findBy([], ['startDate' => 'DESC']);
        return $this->render('staff/roomlisting/bookings.html.twig', [
            'page_title' => 'All Bookings',
            'bookings' => $allBookings,
        ]);
    }
}
