<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\BookingRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'booking')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_booking = null;

    public function getId_booking(): ?int
    {
        return $this->id_booking;
    }

    public function setId_booking(int $id_booking): self
    {
        $this->id_booking = $id_booking;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'id_event', referencedColumnName: 'id_event')]
    private ?Event $event = null;

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'id_location', referencedColumnName: 'id_location')]
    private ?Location $location = null;

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotBlank(message: "Start date is required.")]
    #[Assert\Type(type: \DateTimeInterface::class, message: "Start date must be a valid date.")]
    #[Assert\GreaterThanOrEqual("today", message: "Start date cannot be in the past.")]
    private ?\DateTimeInterface $start_date = null;

    public function getStart_date(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStart_date(\DateTimeInterface $start_date): self
    {
        $this->start_date = $start_date;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotBlank(message: "End date is required.")]
    #[Assert\Type(type: \DateTimeInterface::class, message: "End date must be a valid date.")]
    #[Assert\Expression(
    "this.getStartDate() !== null and this.getEndDate() !== null ? this.getEndDate() > this.getStartDate() : true",
        message: "End date must be after the start date."
    )]
    private ?\DateTimeInterface $end_date = null;

    public function getEnd_date(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEnd_date(\DateTimeInterface $end_date): self
    {
        $this->end_date = $end_date;
        return $this;
    }

    public function getIdBooking(): ?int
    {
        return $this->id_booking;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(?\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEndDate(?\DateTimeInterface $end_date): static
    {
        $this->end_date = $end_date;

        return $this;
    }

}
