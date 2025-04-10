<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CartRepository;

#[ORM\Entity(repositoryClass: CartRepository::class)]
#[ORM\Table(name: 'cart')]
class Cart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'cart_id', type: 'integer')]
    private ?int $cart_id = null;
    public function getCart_id(): ?int
    {
        return $this->cart_id;
    }

    public function setCart_id(int $cart_id): self
    {
        $this->cart_id = $cart_id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'carts')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user')]
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

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $total_price = null;  // ✅ Type string obligatoire en Symfony pour DECIMAL

    public function getTotalPrice(): ?string
    {
        return $this->total_price;
    }

    public function setTotalPrice(?string $total_price): static
    {
        $this->total_price = $total_price;
        return $this;
    }


    #[ORM\OneToOne(targetEntity: Order::class, mappedBy: 'cart')]
    private ?Order $order = null;

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Product::class, inversedBy: 'carts')]
    #[ORM\JoinTable(
        name: 'cart_product',
        joinColumns: [new ORM\JoinColumn(name: 'cart_id', referencedColumnName: 'cart_id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'product_id', referencedColumnName: 'product_id')]
    )]
    private Collection $products;
    
    public function __construct() {
        $this->products = new ArrayCollection();
    }
    
    public function getProducts(): Collection {
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

    public function getCartId(): ?int
    {
        return $this->cart_id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
