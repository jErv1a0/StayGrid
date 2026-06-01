<?php

namespace App\Entity;

use App\Repository\RoomListingRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/rooms', security: "is_granted('PUBLIC_ACCESS')"),
        new Get(uriTemplate: '/rooms/{id}', security: "is_granted('PUBLIC_ACCESS')"),
    ]
)]
#[ORM\Table(name: 'roomlisting')]
#[ORM\UniqueConstraint(name: 'uniq_room_number', fields: ['number'])]
#[Index(name: 'room_category_idx', columns: ['category'])]
#[Index(name: 'room_is_available_idx', columns: ['isAvailable'])]
#[ORM\Entity(repositoryClass: RoomListingRepository::class)]
class RoomListing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['room:read', 'room:write'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['room:read', 'room:write'])]
    private ?string $number = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['room:read', 'room:write'])]
    private ?string $category = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['room:read', 'room:write'])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['room:read', 'room:write'])]
    private ?int $capacity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, name: 'price')]
    #[Groups(['room:read', 'room:write'])]
    private ?string $pricePerNight = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, name: 'price_per_hour', nullable: true)]
    #[Groups(['room:read', 'room:write'])]
    private ?string $pricePerHour = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => true])]
    #[Groups(['room:read', 'room:write'])]
    private ?bool $isAvailable = true;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['room:read', 'room:write'])]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['room:read', 'room:write'])]
    private ?string $location = null;

    // Rental dates are stored on Booking entities now (normalized)

    // Admin-blocked room flag
    #[ORM\Column(type: 'boolean')]
    #[Groups(['room:read', 'room:write'])]
    private bool $isBlocked = false;

    // One Room can have many Bookings
    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Booking::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['room:read'])]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): static
    {
        $this->capacity = $capacity;
        return $this;
    }

    public function getPricePerNight(): ?float
    {
        return $this->pricePerNight !== null ? (float)$this->pricePerNight : null;
    }

    public function setPricePerNight(?float $price): static
    {
        $this->pricePerNight = $price !== null ? number_format($price, 2, '.', '') : null;
        return $this;
    }

    public function getPricePerHour(): ?float
    {
        return $this->pricePerHour !== null ? (float)$this->pricePerHour : null;
    }

    public function setPricePerHour(?float $price): static
    {
        $this->pricePerHour = $price !== null ? number_format($price, 2, '.', '') : null;
        return $this;
    }

    public function isAvailable(): bool
    {

        return $this->isAvailable ?? true;
    }

    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Return the normalized image path for display in templates/API.
     * If image is not set, returns null (let caller handle fallback).
     * If image already starts with 'images/' or 'uploads/', returns as-is.
     * Otherwise, prepends 'uploads/rooms/' to the filename.
     */
    #[Groups(['room:read'])]
    public function getImagePath(): ?string
    {
        if (!$this->image) {
            return null;
        }

        // If already has path prefix (images/ or uploads/), return as-is
        if (str_starts_with($this->image, 'images/') || str_starts_with($this->image, 'uploads/')) {
            return $this->image;
        }

        // Otherwise, default to the known uploads directory
        return 'uploads/rooms/' . $this->image;
    }

    /**
     * Return a human-friendly title for the room.
     * Falls back to category and number when appropriate.
     */
    public function getTitle(): string
    {
        $parts = [];

        if ($this->category) {
            $parts[] = $this->category;
        }

        if ($this->number) {
            $parts[] = 'Room ' . $this->number;
        }

        if (count($parts) > 0) {
            return implode(' — ', $parts);
        }

        return 'Room';
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    // Rental dates moved to Booking; keep room entity focused on static attributes

    // ─── ADMIN-BLOCKED ROOM ────────────────────────────────────────
    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): static
    {
        $this->isBlocked = $isBlocked;
        return $this;
    }

    // ─── HELPER FUNCTIONS ──────────────────────────────────────────
    public function getDurationDays(): ?int
    {
        // Deprecated: Room-level duration removed; compute from first booking if needed
        foreach ($this->bookings as $booking) {
            if ($booking->getStartDate() && $booking->getEndDate()) {
                return $booking->getStartDate()->diff($booking->getEndDate())->days;
            }
        }
        return null;
    }

    // Check if the room is occupied based on current bookings
    public function isOccupied(): bool
    {
        $today = new \DateTime();

        foreach ($this->bookings as $booking) {
            if ($booking->getStartDate() <= $today && $booking->getEndDate() >= $today) {
                return true;
            }
        }

        return false;
    }

    public function getStatus(): string
    {
        if ($this->isBlocked) {
            return 'Blocked';
        }

        if ($this->isOccupied()) {
            return 'Occupied';
        }

        return 'Available';
    }

    // ─── RELATIONSHIP WITH BOOKINGS ────────────────────────────────
    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings[] = $booking;
            $booking->setRoom($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getRoom() === $this) {
                $booking->setRoom(null);
            }
        }

        return $this;
    }
}
