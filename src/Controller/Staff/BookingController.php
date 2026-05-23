<?php

namespace App\Controller\Staff;

use App\Entity\Booking;
use App\Entity\LogInUsers;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\LogInUsersRepository;
use App\Repository\RoomListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_STAFF')]
class BookingController extends AbstractController
{
    private \App\Service\ActivityLogger $activityLogger;

    public function __construct(\App\Service\ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    #[Route('/staff/bookings/{id}', name: 'app_staff_booking_show', methods: ['GET'])]
    public function show(?Booking $booking): Response
    {
        if (!$booking) {
            $this->addFlash('error', 'Booking not found.');
            return $this->redirectToRoute('app_staff_roomlisting_bookings');
        }
        return $this->render('staff/roomlisting/booking_show.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/staff/bookings/{id}/edit', name: 'app_staff_booking_edit', methods: ['GET','POST'])]
    public function edit(?Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        if (!$booking) {
            $this->addFlash('error', 'Booking not found.');
            return $this->redirectToRoute('app_staff_roomlisting_bookings');
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
                    return $this->render('staff/roomlisting/booking_edit.html.twig', [
                        'bookingForm' => $form->createView(),
                        'booking' => $booking,
                        'duplicate_booking_error' => 'This room is already booked for the selected dates. Please choose different dates.',
                    ]);
                }
            }

            $em->persist($booking);
            $em->flush();

            $this->activityLogger->log('booking.updated', $this->getUser(), $booking, ['status' => $booking->getStatus()]);

            $this->addFlash('success', 'Booking updated.');
            return $this->redirectToRoute('app_staff_roomlisting_bookings');
        }

        // Get all bookings for the room to show availability
        $bookedDates = [];
        if ($booking->getRoom()) {
            $allBookings = $em->getRepository(Booking::class)->findBy(
                ['room' => $booking->getRoom(), 'status' => 'confirmed'],
                ['startDate' => 'ASC']
            );
            
            foreach ($allBookings as $existingBooking) {
                $currentDate = $existingBooking->getStartDate();
                $endDate = $existingBooking->getEndDate();
                
                while ($currentDate < $endDate) {
                    $bookedDates[] = $currentDate->format('Y-m-d');
                    $currentDate = $currentDate->modify('+1 day');
                }
            }
        }

        return $this->render('staff/roomlisting/booking_edit.html.twig', [
            'bookingForm' => $form->createView(),
            'booking' => $booking,
            'bookedDates' => json_encode(array_unique($bookedDates)),
        ]);
    }

    #[Route('/staff/bookings/new/{roomId?}', name: 'app_staff_booking_new', methods: ['GET','POST'])]
    public function new(Request $request, RoomListingRepository $roomRepo, LogInUsersRepository $userRepo, EntityManagerInterface $em, ?int $roomId = null): Response
    {
        $booking = new Booking();

        // Pre-fill from URL route / query parameters
        $queryRoomId = $roomId ?? $request->query->get('roomId');
        $userId = $request->query->get('userId');

        if ($queryRoomId) {
            $room = $roomRepo->find($queryRoomId);
            if ($room) {
                $booking->setRoom($room);
            }
        }

        if ($userId) {
            $user = $userRepo->find($userId);
            if ($user instanceof LogInUsers) {
                $booking->setUser($user);
            }
        }

        $form = $this->createForm(BookingType::class, $booking, ['is_staff' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ensure a guest user is set:
            if (!$booking->getUser()) {
                $booking->setUser($this->getUser());
            }

            // Ensure room is assigned
            if (!$booking->getRoom() && $queryRoomId) {
                $room = $roomRepo->find($queryRoomId);
                if ($room) {
                    $booking->setRoom($room);
                }
            }

            $status = strtolower((string) ($booking->getStatus() ?? 'confirmed'));
            $shouldCheckOverlap = !in_array($status, ['cancelled', 'rejected'], true);
            if ($shouldCheckOverlap && $booking->getRoom() && $booking->getStartDate() && $booking->getEndDate()) {
                $overlaps = $em->getRepository(Booking::class)->countOverlappingBookings(
                    $booking->getRoom(),
                    $booking->getStartDate(),
                    $booking->getEndDate()
                );

                if ($overlaps > 0) {
                    return $this->render('staff/roomlisting/booking_new.html.twig', [
                        'bookingForm' => $form->createView(),
                        'booking' => $booking,
                        'duplicate_booking_error' => 'This room is already booked for the selected dates. Please choose different dates.',
                    ]);
                }
            }

            $booking->setStatus($booking->getStatus() ?? 'confirmed');
            $em->persist($booking);
            $em->flush();

            $this->activityLogger->log('booking.created', $this->getUser(), $booking, ['status' => $booking->getStatus()]);

            $this->addFlash('success', 'Booking created.');
            return $this->redirectToRoute('app_staff_roomlisting_bookings');
        }

        // Get all bookings for the room to show availability
        $bookedDates = [];
        if ($booking->getRoom()) {
            $allBookings = $em->getRepository(Booking::class)->findBy(
                ['room' => $booking->getRoom(), 'status' => 'confirmed'],
                ['startDate' => 'ASC']
            );
            
            foreach ($allBookings as $existingBooking) {
                $currentDate = $existingBooking->getStartDate();
                $endDate = $existingBooking->getEndDate();
                
                while ($currentDate < $endDate) {
                    $bookedDates[] = $currentDate->format('Y-m-d');
                    $currentDate = $currentDate->modify('+1 day');
                }
            }
        }

        return $this->render('staff/roomlisting/booking_new.html.twig', [
            'bookingForm' => $form->createView(),
            'booking' => $booking,
            'bookedDates' => json_encode(array_unique($bookedDates)),
        ]);
    }

    #[Route('/staff/bookings/{id}/delete', name: 'app_staff_booking_delete', methods: ['POST'])]
    public function delete(?Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        if (!$booking) {
            $this->addFlash('error', 'Booking not found.');
            return $this->redirectToRoute('app_staff_roomlisting_bookings');
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
            return $this->redirectToRoute('app_staff_roomlisting_bookings');
    }
}
