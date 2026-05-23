<?php

namespace App\Controller\User;

use App\Entity\RoomListing;
use App\Entity\Booking;
use App\Entity\Transaction;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/booking')]
#[IsGranted(new Expression("is_granted('ROLE_CLIENT') or is_granted('ROLE_STAFF')"))]
class BookingController extends AbstractController
{
    private \App\Service\ActivityLogger $activityLogger;

    public function __construct(\App\Service\ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    #[Route('/new/{roomId}', name: 'app_booking_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        int $roomId,
        EntityManagerInterface $entityManager
    ): Response {
        // --- 1. Fetch the room from database ---
        $room = $entityManager->getRepository(RoomListing::class)->find($roomId);

        if (!$room) {
            // FIX 1: Corrected redirect route name
            $this->addFlash('error', 'The room you are trying to book does not exist.');
            return $this->redirectToRoute('app_client_booking_index');
        }

        // --- 2. Check room availability ---
        if (!$room->isAvailable() || $room->isBlocked()) {
            $this->addFlash('error', 'This room is currently unavailable for booking.');
            // FIX 2: Corrected redirect route name
            return $this->redirectToRoute('app_client_booking_index');
        }
        // ... (rest of the controller logic is correct)
        
        // --- 3. Create new Booking object ---
        $booking = new Booking();
        $booking->setStatus('pending');

        // --- 4. Create and handle the form ---
        $isStaff = $this->isGranted('ROLE_STAFF');
        $form = $this->createForm(BookingType::class, $booking, ['is_staff' => $isStaff]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // --- 5. Force assign the room after form submission ---
            $booking->setRoom($room);

            // --- 5.5 Set user: if staff selected one, use it; else current user ---
            if (!$booking->getUser()) {
                $booking->setUser($this->getUser());
            }

            // --- 5.6 Validate booking type ---
            $bookingType = $booking->getBookingType() ?? 'daily';

            if ($bookingType === 'hourly') {
                // Hourly booking validation
                $hours = $booking->getNumberOfHours();
                if (!$hours || $hours < 4) {
                    return $this->render('booking/new.html.twig', [
                        'room' => $room,
                        'bookingForm' => $form,
                        'duplicate_booking_error' => 'Hourly bookings require a minimum of 4 hours. Please adjust your booking duration.',
                    ]);
                }

                // For hourly bookings, set dates to same day with times
                $now = new \DateTimeImmutable();
                $startDate = $now;
                $endDate = $now->modify('+' . $hours . ' hours');
                $booking->setStartDate($startDate);
                $booking->setEndDate($endDate);
            } else {
                // Daily booking validation
                $startDate = $booking->getStartDate() ?? new \DateTimeImmutable();
                $endDate = $booking->getEndDate() ?? $startDate->modify('+1 day');

                $booking->setStartDate($startDate);
                $booking->setEndDate($endDate);

                // Validate date range
                if ($startDate >= $endDate) {
                    $this->addFlash('error', 'End date must be after start date.');
                    return $this->redirectToRoute('app_booking_new', ['roomId' => $room->getId()]);
                }
            }

            // --- 6. Check for overlapping bookings (ANY user, same room, overlapping dates/times) ---
            $bookingRepo = $entityManager->getRepository(Booking::class);
            $overlaps = $bookingRepo->countOverlappingBookings($room, $booking->getStartDate(), $booking->getEndDate());
            if ($overlaps > 0) {
                return $this->render('booking/new.html.twig', [
                    'room' => $room,
                    'bookingForm' => $form,
                    'duplicate_booking_error' => 'This time slot is already booked. Please choose a different time.',
                ]);
            }

            // --- 7. Confirm booking ---
            $booking->setStatus('confirmed');

            // --- 8. Persist booking ---
            $entityManager->persist($booking);
            
            // --- 9. Calculate Total Price using the new method ---
            $totalAmount = $booking->calculateTotalPrice();

            // --- 10. Create transaction ---
            $transaction = new Transaction();
            $transaction->setBooking($booking);
            
            // FIX: Set the mandatory room and user properties on the Transaction entity
            $transaction->setRoom($room); 
            $transaction->setUser($this->getUser()); 

            $transaction->setAmount((string)$totalAmount); 
            
            $transaction->setCheckIn($booking->getStartDate());
            $transaction->setCheckOut($booking->getEndDate());
            $transaction->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($transaction);

            // --- 11. Flush to database ---
            $entityManager->flush();

            // Log activity
            $this->activityLogger->log('booking.created', $this->getUser(), $booking, ['room' => $room->getId(), 'amount' => $totalAmount]);

            $this->addFlash('success', 'Booking successfully created! Total cost: ₱' . number_format($totalAmount, 2));
            return $this->redirectToRoute('app_client_booking_show', ['id' => $booking->getId()]);
        }

        // --- 12. Render booking form template ---
        $bookingRepo = $entityManager->getRepository(Booking::class);
        
        // Get all bookings for this room (active/confirmed)
        $allBookings = $bookingRepo->findBy(
            ['room' => $room, 'status' => 'confirmed'],
            ['startDate' => 'ASC']
        );
        
        // Build booked dates list for calendar visualization
        $bookedDates = [];
        foreach ($allBookings as $existingBooking) {
            $currentDate = $existingBooking->getStartDate();
            $endDate = $existingBooking->getEndDate();
            
            while ($currentDate < $endDate) {
                $bookedDates[] = $currentDate->format('Y-m-d');
                $currentDate = $currentDate->modify('+1 day');
            }
        }
        
        return $this->render('booking/new.html.twig', [
            'room' => $room,
            'bookingForm' => $form,
            'bookedDates' => json_encode(array_unique($bookedDates)),
        ]);
    }

    // ... (index and show methods remain the same) ...
    #[Route('/', name: 'app_client_booking_index', methods: ['GET'])]
    public function index(BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        $bookings = $bookingRepository->findBy(['user' => $user], ['startDate' => 'DESC']);

        return $this->render('booking/index.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/{id}', name: 'app_client_booking_show', methods: ['GET'])]
    public function show(?Booking $booking): Response
    {
        if (!$booking) {
            $this->addFlash('error', 'Booking not found.');
            return $this->redirectToRoute('app_client_booking_index');
        }

        // Allow booking owner to view; also allow staff users to view any booking
        if ($booking->getUser() !== $this->getUser() && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('You do not have permission to view this booking.');
        }

        return $this->render('booking/show.html.twig', [
            'booking' => $booking,
        ]);
    }
}