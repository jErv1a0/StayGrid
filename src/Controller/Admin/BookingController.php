<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Form\BookingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/bookings', name: 'app_admin_bookings_')]
class BookingController extends AbstractController
{
    private \App\Service\ActivityLogger $activityLogger;

    public function __construct(\App\Service\ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $bookings = $em->getRepository(Booking::class)->findBy([], ['startDate' => 'DESC']);

        return $this->render('admin/bookings/index.html.twig', [
            'page_title' => 'All Bookings',
            'bookings' => $bookings,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $status = strtolower((string) ($booking->getStatus() ?? 'confirmed'));
            $shouldCheckOverlap = !in_array($status, ['cancelled', 'rejected'], true);
            if ($shouldCheckOverlap && $booking->getRoom() && $booking->getStartDate() && $booking->getEndDate()) {
                $overlaps = $em->getRepository(Booking::class)->countOverlappingBookings(
                    $booking->getRoom(),
                    $booking->getStartDate(),
                    $booking->getEndDate()
                );

                if ($overlaps > 0) {
                    $this->addFlash('error', 'Booking conflict: this room is already booked for the selected dates.');
                    return $this->redirectToRoute('app_admin_bookings_new');
                }
            }

            $booking->setStatus($booking->getStatus() ?? 'confirmed');
            $em->persist($booking);
            $em->flush();

            $this->activityLogger->log('booking.created', $this->getUser(), $booking, ['status' => $booking->getStatus()]);

            $this->addFlash('success', 'Booking created.');
            return $this->redirectToRoute('app_admin_bookings_index');
        }

        return $this->render('admin/bookings/new.html.twig', [
            'bookingForm' => $form->createView(),
            'booking' => $booking,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(?Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        if (!$booking) {
            $this->addFlash('error', 'Booking not found.');
            return $this->redirectToRoute('app_admin_bookings_index');
        }

        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $status = strtolower((string) $booking->getStatus());
            $shouldCheckOverlap = !in_array($status, ['cancelled', 'rejected'], true);
            if ($shouldCheckOverlap && $booking->getRoom() && $booking->getStartDate() && $booking->getEndDate()) {
                $overlaps = $em->getRepository(Booking::class)->countOverlappingBookings(
                    $booking->getRoom(),
                    $booking->getStartDate(),
                    $booking->getEndDate(),
                    $booking->getId()
                );

                if ($overlaps > 0) {
                    $this->addFlash('error', 'Booking conflict: this room is already booked for the selected dates.');
                    return $this->redirectToRoute('app_admin_bookings_edit', ['id' => $booking->getId()]);
                }
            }

            $em->flush();
            $this->activityLogger->log('booking.updated', $this->getUser(), $booking, ['status' => $booking->getStatus()]);
            $this->addFlash('success', 'Booking updated.');
            return $this->redirectToRoute('app_admin_bookings_index');
        }

        return $this->render('admin/bookings/edit.html.twig', [
            'bookingForm' => $form->createView(),
            'booking' => $booking,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(?Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        if (!$booking) {
            $this->addFlash('error', 'Booking not found.');
            return $this->redirectToRoute('app_admin_bookings_index');
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete-booking-' . $booking->getId(), $token)) {
            $this->activityLogger->log('booking.deleted', $this->getUser(), $booking, ['status' => $booking->getStatus()]);
            $em->remove($booking);
            $em->flush();
            $this->addFlash('success', 'Booking deleted.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_admin_bookings_index');
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(?Booking $booking): Response
    {
        if (!$booking) {
            $this->addFlash('error', 'Booking not found.');
            return $this->redirectToRoute('app_admin_bookings_index');
        }

        return $this->render('admin/bookings/show.html.twig', [
            'booking' => $booking,
        ]);
    }
}
