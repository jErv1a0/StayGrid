<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\RoomListing;
use App\Entity\LogInUsers;
use App\Entity\Transaction;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete(),
    ]
)]
#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['booking:read', 'booking:write'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?RoomListing $room = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['booking:read', 'booking:write'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['booking:read', 'booking:write'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 50, options: ['default' => 'pending'])]
    #[Groups(['booking:read', 'booking:write'])]
    private ?string $status = 'pending';

    #[ORM\ManyToOne(targetEntity: LogInUsers::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?LogInUsers $user = null;

    #[ORM\OneToMany(mappedBy: 'booking', targetEntity: Transaction::class, cascade: ['remove'], orphanRemoval: true)]
    #[Groups(['booking:read'])]
    private Collection $transactions;

    #[ORM\Column(length: 20, options: ['default' => 'daily'])]
    #[Groups(['booking:read', 'booking:write'])]
    private ?string $bookingType = 'daily'; // 'daily' or 'hourly'

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?int $numberOfHours = null;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->bookingType = 'daily';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoom(): ?RoomListing
    {
        return $this->room;
    }

    public function setRoom(?RoomListing $room): self
    {
        $this->room = $room;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getUser(): ?LogInUsers
    {
        return $this->user;
    }

    public function setUser(?LogInUsers $user): self
    {
        $this->user = $user;
        return $this;
    }

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context, $payload): void
    {
        // startDate should not be in the past (compared to server local "today")
        if ($this->startDate instanceof \DateTimeInterface) {
            if ($this->startDate instanceof \DateTimeImmutable) {
                $start = $this->startDate;
            } elseif ($this->startDate instanceof \DateTime) {
                $start = \DateTimeImmutable::createFromMutable($this->startDate);
            } else {
                $start = new \DateTimeImmutable($this->startDate->format('Y-m-d'));
            }
            $today = new \DateTimeImmutable('today');
            if ($start < $today) {
                $context->buildViolation('Start date cannot be in the past.')
                    ->atPath('startDate')
                    ->addViolation();
            }
        }

        // endDate must be after startDate
        if ($this->startDate instanceof \DateTimeInterface && $this->endDate instanceof \DateTimeInterface) {
            if ($this->startDate instanceof \DateTimeImmutable) {
                $start = $this->startDate;
            } elseif ($this->startDate instanceof \DateTime) {
                $start = \DateTimeImmutable::createFromMutable($this->startDate);
            } else {
                $start = new \DateTimeImmutable($this->startDate->format('Y-m-d'));
            }
            if ($this->endDate instanceof \DateTimeImmutable) {
                $end = $this->endDate;
            } elseif ($this->endDate instanceof \DateTime) {
                $end = \DateTimeImmutable::createFromMutable($this->endDate);
            } else {
                $end = new \DateTimeImmutable($this->endDate->format('Y-m-d'));
            }
            if ($end <= $start) {
                $context->buildViolation('End date must be after start date.')
                    ->atPath('endDate')
                    ->addViolation();
            }
        }
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function getBookingType(): ?string
    {
        return $this->bookingType ?? 'daily';
    }

    public function setBookingType(string $bookingType): self
    {
        $this->bookingType = $bookingType;
        return $this;
    }

    public function getNumberOfHours(): ?int
    {
        return $this->numberOfHours;
    }

    public function setNumberOfHours(?int $numberOfHours): self
    {
        $this->numberOfHours = $numberOfHours;
        return $this;
    }

    /**
     * Calculate total booking price based on booking type
     */
    public function calculateTotalPrice(): float
    {
        if (!$this->room) {
            return 0.0;
        }

        if ($this->bookingType === 'hourly' && $this->numberOfHours) {
            $pricePerHour = $this->room->getPricePerHour() ?? 0;
            return (float)$pricePerHour * $this->numberOfHours;
        }

        // Default: daily calculation
        if ($this->startDate && $this->endDate) {
            $interval = $this->startDate->diff($this->endDate);
            $numberOfNights = $interval->days > 0 ? $interval->days : 1;
            $pricePerNight = $this->room->getPricePerNight() ?? 0;
            return (float)$pricePerNight * $numberOfNights;
        }

        return 0.0;
    }

    /**
     * Get display label for this booking
     */
    public function getDisplayLabel(): string
    {
        if ($this->bookingType === 'hourly') {
            return $this->numberOfHours . ' hours';
        }
        
        if ($this->startDate && $this->endDate) {
            $interval = $this->startDate->diff($this->endDate);
            $days = $interval->days;
            return $days . ' day' . ($days !== 1 ? 's' : '');
        }

        return 'N/A';
    }
}