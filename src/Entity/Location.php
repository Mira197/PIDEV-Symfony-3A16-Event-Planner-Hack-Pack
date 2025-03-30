<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Enum\City;

use App\Repository\LocationRepository;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Table(name: 'location')]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_location = null;

    public function getId_location(): ?int
    {
        return $this->id_location;
    }

    public function setId_location(int $id_location): self
    {
        $this->id_location = $id_location;
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $address = null;

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    #[ORM\Column(type: 'string', enumType: City::class)]
    private ?City $city = null;

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): self
{
    $this->city = $city;
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
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $dimension = null;

    public function getDimension(): ?string
    {
        return $this->dimension;
    }

    public function setDimension(string $dimension): self
    {
        $this->dimension = $dimension;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $price = null;

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
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

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $has_3d_tour = null;

    public function isHas_3d_tour(): ?bool
    {
        return $this->has_3d_tour;
    }

    public function setHas_3d_tour(?bool $has_3d_tour): self
    {
        $this->has_3d_tour = $has_3d_tour;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $table_set_count = null;

    public function getTable_set_count(): ?int
    {
        return $this->table_set_count;
    }

    public function setTable_set_count(?int $table_set_count): self
    {
        $this->table_set_count = $table_set_count;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $include_corner_plants = null;

    public function isInclude_corner_plants(): ?bool
    {
        return $this->include_corner_plants;
    }

    public function setInclude_corner_plants(?bool $include_corner_plants): self
    {
        $this->include_corner_plants = $include_corner_plants;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $window_style = null;

    public function getWindow_style(): ?string
    {
        return $this->window_style;
    }

    public function setWindow_style(?string $window_style): self
    {
        $this->window_style = $window_style;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $door_style = null;

    public function getDoor_style(): ?string
    {
        return $this->door_style;
    }

    public function setDoor_style(?string $door_style): self
    {
        $this->door_style = $door_style;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $include_ceiling_lights = null;

    public function isInclude_ceiling_lights(): ?bool
    {
        return $this->include_ceiling_lights;
    }

    public function setInclude_ceiling_lights(?bool $include_ceiling_lights): self
    {
        $this->include_ceiling_lights = $include_ceiling_lights;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $light_color = null;

    public function getLight_color(): ?string
    {
        return $this->light_color;
    }

    public function setLight_color(?string $light_color): self
    {
        $this->light_color = $light_color;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'location')]
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

    public function getIdLocation(): ?int
    {
        return $this->id_location;
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

    public function has3dTour(): ?bool
    {
        return $this->has_3d_tour;
    }

    public function setHas3dTour(?bool $has_3d_tour): static
    {
        $this->has_3d_tour = $has_3d_tour;

        return $this;
    }

    public function getTableSetCount(): ?int
    {
        return $this->table_set_count;
    }

    public function setTableSetCount(?int $table_set_count): static
    {
        $this->table_set_count = $table_set_count;

        return $this;
    }

    public function isIncludeCornerPlants(): ?bool
    {
        return $this->include_corner_plants;
    }

    public function setIncludeCornerPlants(?bool $include_corner_plants): static
    {
        $this->include_corner_plants = $include_corner_plants;

        return $this;
    }

    public function getWindowStyle(): ?string
    {
        return $this->window_style;
    }

    public function setWindowStyle(?string $window_style): static
    {
        $this->window_style = $window_style;

        return $this;
    }

    public function getDoorStyle(): ?string
    {
        return $this->door_style;
    }

    public function setDoorStyle(?string $door_style): static
    {
        $this->door_style = $door_style;

        return $this;
    }

    public function isIncludeCeilingLights(): ?bool
    {
        return $this->include_ceiling_lights;
    }

    public function setIncludeCeilingLights(?bool $include_ceiling_lights): static
    {
        $this->include_ceiling_lights = $include_ceiling_lights;

        return $this;
    }

    public function getLightColor(): ?string
    {
        return $this->light_color;
    }

    public function setLightColor(?string $light_color): static
    {
        $this->light_color = $light_color;

        return $this;
    }

}
