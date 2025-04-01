<?php

namespace App\Entity;

use App\Enum\City;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EventRepository;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'event')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_event = null;

    public function getId_event(): ?int
    {
        return $this->id_event;
    }

    public function setId_event(int $id_event): self
    {
        $this->id_event = $id_event;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $capacity = null;

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): self
    {
        $this->capacity = $capacity;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $city = null;

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'events')]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user')]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    #[ORM\Column(type: 'blob', nullable: true)]
    private $image_data = null;

    public function getImage_data()
    {
        return $this->image_data;
    }

    public function setImage_data(?string $image_data): self
    {
        $this->image_data = $image_data;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $image_filename = null;

    public function getImage_filename(): ?string
    {
        return $this->image_filename;
    }

    public function setImage_filename(?string $image_filename): self
    {
        $this->image_filename = $image_filename;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'event')]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        if (!$this->bookings instanceof Collection) {
            $this->bookings = new ArrayCollection();
        }
        return $this->bookings;
    }

    public function addBooking(Booking $booking): self
    {
        if (!$this->getBookings()->contains($booking)) {
            $this->getBookings()->add($booking);
        }
        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        $this->getBookings()->removeElement($booking);
        return $this;
    }

    public function getIdEvent(): ?int
    {
        return $this->id_event;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEndDate(\DateTimeInterface $end_date): static
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function getImageData()
    {
        return $this->image_data;
    }

    public function setImageData($image_data): static
    {
        $this->image_data = $image_data;

        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->image_filename;
    }

    public function setImageFilename(?string $image_filename): static
    {
        $this->image_filename = $image_filename;

        return $this;
    }

}
