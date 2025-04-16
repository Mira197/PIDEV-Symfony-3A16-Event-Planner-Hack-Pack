<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\OrderRepository;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'order_id', type: 'integer')]
    private ?int $order_id = null;

    #[ORM\OneToOne(targetEntity: Cart::class, inversedBy: 'order')]
    #[ORM\JoinColumn(name: 'cart_id', referencedColumnName: 'cart_id', unique: true)]
    private ?Cart $cart = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user')]
    #[Assert\NotNull(message: "User cannot be null.")]
    private ?User $user = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Choice(choices: ['PENDING', 'CONFIRMED', 'CANCELLED', 'DELIVERED'])]
    private ?string $status = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Please select a payment method.")]
    private ?string $payment_method = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "Delivery address is required.")]
    #[Assert\Length(
        min: 10,
        minMessage: "This value is too short. It should have {{ limit }} characters or more."
    )]
    private ?string $exact_address = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: "Please select a valid event date.")]
    #[Assert\GreaterThan("today", message: "The event date must be in the future.")]
    private ?\DateTimeInterface $event_date = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\GreaterThan(
        "today",
        message: "La date de commande doit être dans le futur"
    )]
    private ?\DateTimeInterface $ordered_at = null;
   
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotNull(message: "Total price cannot be null.")]
    #[Assert\PositiveOrZero(message: "Total price must be zero or positive.")]
    private ?float $total_price = null;

    // ---------------- Getters & Setters ----------------

    public function getOrderId(): ?int
    {
        return $this->order_id;
    }

    public function setOrderId(int $order_id): self
    {
        $this->order_id = $order_id;
        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): self
    {
        $this->cart = $cart;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(string $payment_method): self
    {
        $this->payment_method = $payment_method;
        return $this;
    }

    public function getExactAddress(): ?string
    {
        return $this->exact_address;
    }

    public function setExactAddress(string $exact_address): self
    {
        $this->exact_address = $exact_address;
        return $this;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->event_date;
    }

    public function setEventDate(?\DateTimeInterface $event_date): self
    {
        $this->event_date = $event_date;
        return $this;
    }

    public function getOrderedAt(): ?\DateTimeInterface
    {
        return $this->ordered_at;
    }

    public function setOrderedAt(?\DateTimeInterface $ordered_at): self
    {
        $this->ordered_at = $ordered_at;
        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->total_price;
    }

    public function setTotalPrice(float $total_price): self
    {
        $this->total_price = $total_price;
        return $this;
    }
}
