<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ProductRepository;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $product_id = null;

    public function getProduct_id(): ?int
    {
        return $this->product_id;
    }

    public function setProduct_id(int $product_id): self
    {
        $this->product_id = $product_id;
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

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $price = null;

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;
        return $this;
    }

    /*#[ORM\ManyToOne(targetEntity: Stock::class, inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'stock_id', referencedColumnName: 'stock_id')]
    private ?int $stock_id = null;

public function getStockId(): ?int
{
    return $this->stock_id;
}

public function setStockId(?int $stockId): self
{
    $this->stock_id = $stockId;
    return $this;
}*/
#[ORM\ManyToOne(targetEntity: Stock::class, inversedBy: 'products')]
#[ORM\JoinColumn(name: 'stock_id', referencedColumnName: 'stock_id', nullable: false)]
private ?Stock $stock = null;
public function getStock(): ?Stock
{
    return $this->stock;
}

public function setStock(?Stock $stock): self
{
    $this->stock = $stock;
    return $this;
}

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $image_url = null;

    public function getImage_url(): ?string
    {
        return $this->image_url;
    }

    public function setImage_url(?string $image_url): self
    {
        $this->image_url = $image_url;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $category = null;

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'products')]
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $reference = null;

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Cart::class, inversedBy: 'products')]
    #[ORM\JoinTable(
        name: 'cart_product',
        joinColumns: [
            new ORM\JoinColumn(name: 'product_id', referencedColumnName: 'product_id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'cart_id', referencedColumnName: 'cart_id')
        ]
    )]
    private Collection $carts;

    public function __construct()
    {
        $this->carts = new ArrayCollection();
    }

    /**
     * @return Collection<int, Cart>
     */
    public function getCarts(): Collection
    {
        if (!$this->carts instanceof Collection) {
            $this->carts = new ArrayCollection();
        }
        return $this->carts;
    }

    public function addCart(Cart $cart): self
    {
        if (!$this->getCarts()->contains($cart)) {
            $this->getCarts()->add($cart);
        }
        return $this;
    }

    public function removeCart(Cart $cart): self
    {
        $this->getCarts()->removeElement($cart);
        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->product_id;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImageUrl(?string $image_url): static
    {
        $this->image_url = $image_url;

        return $this;
    }

}
