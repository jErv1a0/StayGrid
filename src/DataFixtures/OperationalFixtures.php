<?php

namespace App\DataFixtures;

use App\Entity\ActivityLog;
use App\Entity\Booking;
use App\Entity\Feedback;
use App\Entity\LogInUsers;
use App\Entity\RoomListing;
use App\Entity\Transaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OperationalFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            RoomListingFixtures::class,
            StaffUserFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $room101 = $manager->getRepository(RoomListing::class)->findOneBy(['number' => '101']);
        $room201 = $manager->getRepository(RoomListing::class)->findOneBy(['number' => '201']);
        $admin = $manager->getRepository(LogInUsers::class)->findOneBy(['email' => 'admin@staygrid.com']);
        $staff = $manager->getRepository(LogInUsers::class)->findOneBy(['email' => 'staff@gmail.com']);

        if (!$room101 || !$room201 || !$admin || !$staff) {
            return;
        }

        $seedBookings = [
            [
                'room' => $room101,
                'user' => $admin,
                'start' => new \DateTimeImmutable('2026-06-01 14:00:00'),
                'end' => new \DateTimeImmutable('2026-06-05 12:00:00'),
                'status' => 'confirmed',
                'bookingType' => 'daily',
            ],
            [
                'room' => $room201,
                'user' => $staff,
                'start' => new \DateTimeImmutable('2026-06-10 14:00:00'),
                'end' => new \DateTimeImmutable('2026-06-12 12:00:00'),
                'status' => 'confirmed',
                'bookingType' => 'daily',
            ],
        ];

        $bookings = [];

        foreach ($seedBookings as $seedBooking) {
            $existingBooking = $manager->getRepository(Booking::class)->findOneBy([
                'room' => $seedBooking['room'],
                'user' => $seedBooking['user'],
                'startDate' => $seedBooking['start'],
                'endDate' => $seedBooking['end'],
            ]);

            if ($existingBooking instanceof Booking) {
                $bookings[] = $existingBooking;
                continue;
            }

            $booking = new Booking();
            $booking->setRoom($seedBooking['room']);
            $booking->setUser($seedBooking['user']);
            $booking->setStartDate($seedBooking['start']);
            $booking->setEndDate($seedBooking['end']);
            $booking->setStatus($seedBooking['status']);
            $booking->setBookingType($seedBooking['bookingType']);
            $manager->persist($booking);
            $bookings[] = $booking;
        }

        $manager->flush();

        foreach ($bookings as $index => $booking) {
            $existingTransaction = $manager->getRepository(Transaction::class)->findOneBy(['booking' => $booking]);

            if ($existingTransaction instanceof Transaction) {
                continue;
            }

            $transaction = new Transaction();
            $transaction->setBooking($booking);
            $transaction->setRoom($booking->getRoom());
            $transaction->setUser($booking->getUser() ?? $admin);
            $transaction->setAmount(number_format((float) $booking->calculateTotalPrice(), 2, '.', ''));
            $transaction->setCheckIn($booking->getStartDate());
            $transaction->setCheckOut($booking->getEndDate());
            $transaction->setStatus('BOOKED');
            $transaction->setCreatedAt(new \DateTimeImmutable(sprintf('2026-05-%02d 09:00:00', 26 + $index)));
            $manager->persist($transaction);
        }

        $feedbackSeeds = [
            [
                'name' => 'Maria Santos',
                'email' => 'maria@example.com',
                'content' => 'Smooth booking flow and clean room presentation.',
            ],
            [
                'name' => 'David Reyes',
                'email' => 'david@example.com',
                'content' => 'Staff responded quickly and the room matched the listing.',
            ],
        ];

        foreach ($feedbackSeeds as $feedbackSeed) {
            $existingFeedback = $manager->getRepository(Feedback::class)->findOneBy([
                'email' => $feedbackSeed['email'],
                'content' => $feedbackSeed['content'],
            ]);

            if ($existingFeedback instanceof Feedback) {
                continue;
            }

            $feedback = new Feedback();
            $feedback->setName($feedbackSeed['name']);
            $feedback->setEmail($feedbackSeed['email']);
            $feedback->setContent($feedbackSeed['content']);
            $feedback->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($feedback);
        }

        foreach ($bookings as $booking) {
            $existingLog = $manager->getRepository(ActivityLog::class)->findOneBy([
                'action' => 'booking.created',
                'targetType' => 'Booking',
                'targetId' => $booking->getId(),
            ]);

            if ($existingLog instanceof ActivityLog) {
                continue;
            }

            $activityLog = new ActivityLog();
            $activityLog->setAction('booking.created');
            $activityLog->setActorType('user');
            $activityLog->setActorId($booking->getUser()?->getId());
            $activityLog->setActorEmail($booking->getUser()?->getEmail());
            $activityLog->setTargetType('Booking');
            $activityLog->setTargetId($booking->getId());
            $activityLog->setDetails([
                'room' => $booking->getRoom()?->getNumber(),
                'status' => $booking->getStatus(),
            ]);
            $manager->persist($activityLog);
        }

        $manager->flush();
    }
}