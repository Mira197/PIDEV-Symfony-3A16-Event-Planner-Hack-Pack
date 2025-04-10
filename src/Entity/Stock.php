<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;


use App\Repository\StockRepository;

#[ORM\Entity(repositoryClass: StockRepository::class)]
#[ORM\Table(name: 'stock')]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $stock_id = null;

    public function getStock_id(): ?int
    {
        return $this->stock_id;
    }

    public function setStock_id(int $stock_id): self
    {
        $this->stock_id = $stock_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: "La quantité disponible est obligatoire.")]
    #[Assert\GreaterThanOrEqual(
        propertyPath: "minimum_quantity",
        message: "La quantité disponible doit être supérieure ou égale à la quantité minimale."
    )]
    private ?int $available_quantity = null;

    public function getAvailable_quantity(): ?int
    {
        return $this->available_quantity;
    }

    public function setAvailable_quantity(int $available_quantity): self
    {
        $this->available_quantity = $available_quantity;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: "La quantité minimale est obligatoire.")]
    #[Assert\PositiveOrZero(message: "La quantité minimale ne peut pas être négative.")]
    private ?int $minimum_quantity = null;

    public function getMinimum_quantity(): ?int
    {
        return $this->minimum_quantity;
    }

    public function setMinimum_quantity(int $minimum_quantity): self
    {
        $this->minimum_quantity = $minimum_quantity;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $last_update = null;

    public function getLast_update(): ?\DateTimeInterface
    {
        return $this->last_update;
    }

    public function setLast_update(\DateTimeInterface $last_update): self
    {
        $this->last_update = $last_update;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'stocks')]
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

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'stock')]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        if (!$this->products instanceof Collection) {
            $this->products = new ArrayCollection();
        }
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->getProducts()->contains($product)) {
            $this->getProducts()->add($product);
        }
        return $this;
    }

    public function removeProduct(Product $product): self
    {
        $this->getProducts()->removeElement($product);
        return $this;
    }

    public function getStockId(): ?int
    {
        return $this->stock_id;
    }

    public function getAvailableQuantity(): ?int
    {
        return $this->available_quantity;
    }

    public function setAvailableQuantity(int $available_quantity): static
    {
        $this->available_quantity = $available_quantity;

        return $this;
    }

    public function getMinimumQuantity(): ?int
    {
        return $this->minimum_quantity;
    }

    public function setMinimumQuantity(int $minimum_quantity): static
    {
        $this->minimum_quantity = $minimum_quantity;

        return $this;
    }

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->last_update;
    }

    public function setLastUpdate(\DateTimeInterface $last_update): static
    {
        $this->last_update = $last_update;

        return $this;
    }

}
