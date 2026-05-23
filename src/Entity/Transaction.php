<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use App\Entity\LogInUsers;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'transaction')]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Booking relationship and property — cascade on delete at DB level
    #[ORM\ManyToOne(targetEntity: Booking::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Booking $booking = null;

    #[ORM\ManyToOne(targetEntity: RoomListing::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?RoomListing $room = null;

    #[ORM\ManyToOne(targetEntity: LogInUsers::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?LogInUsers $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)] 
    private ?string $amount = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $checkIn;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $checkOut;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'BOOKED';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // FIX: Getter/Setter for Booking
    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): self
    {
        $this->booking = $booking;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getRoom(): ?RoomListing
    {
        return $this->room;
    }
    
    public function setRoom(RoomListing $room): self
    {
        $this->room = $room;
        return $this;
    }

    public function getUser(): ?LogInUsers
    {
        return $this->user;
    }

    public function setUser(LogInUsers $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getCheckIn(): \DateTimeInterface
    {
        return $this->checkIn;
    }

    public function setCheckIn(\DateTimeInterface $checkIn): self
    {
        $this->checkIn = $checkIn;
        return $this;
    }

    public function getCheckOut(): \DateTimeInterface
    {
        return $this->checkOut;
    }

    public function setCheckOut(\DateTimeInterface $checkOut): self
    {
        $this->checkOut = $checkOut;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
    
    // FIX: Getter/Setter for createdAt
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}