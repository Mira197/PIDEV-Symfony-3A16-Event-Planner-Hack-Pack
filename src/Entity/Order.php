<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    public function getOrder_id(): ?int
    {
        return $this->order_id;
    }

    public function setOrder_id(int $order_id): self
    {
        $this->order_id = $order_id;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: Cart::class, inversedBy: 'order')]
    #[ORM\JoinColumn(name: 'cart_id', referencedColumnName: 'cart_id', unique: true)]
    private ?Cart $cart = null;

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): self
    {
        $this->cart = $cart;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders')]
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank]
    private ?string $payment_method = null;

    public function getPayment_method(): ?string
    {
        return $this->payment_method;
    }

    public function setPayment_method(string $payment_method): self
    {
        $this->payment_method = $payment_method;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10)]
    private ?string $exact_address = null;

    public function getExact_address(): ?string
    {
        return $this->exact_address;
    }

    public function setExact_address(string $exact_address): self
    {
        $this->exact_address = $exact_address;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotNull]
    #[Assert\GreaterThan("today")]
    private ?\DateTimeInterface $event_date = null;

    public function getEvent_date(): ?\DateTimeInterface
    {
        return $this->event_date;
    }

    public function setEvent_date(\DateTimeInterface $event_date): self
    {
        $this->event_date = $event_date;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $ordered_at = null;

    public function getOrdered_at(): ?\DateTimeInterface
    {
        return $this->ordered_at;
    }

    public function setOrdered_at(\DateTimeInterface $ordered_at): self
    {
        $this->ordered_at = $ordered_at;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $total_price = null;

    public function getTotal_price(): ?float
    {
        return $this->total_price;
    }

    public function setTotal_price(float $total_price): self
    {
        $this->total_price = $total_price;
        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->order_id;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(string $payment_method): static
    {
        $this->payment_method = $payment_method;

        return $this;
    }

    public function getExactAddress(): ?string
    {
        return $this->exact_address;
    }

    public function setExactAddress(string $exact_address): static
    {
        $this->exact_address = $exact_address;

        return $this;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->event_date;
    }

    public function setEventDate(\DateTimeInterface $event_date): static
    {
        $this->event_date = $event_date;

        return $this;
    }

    public function getOrderedAt(): ?\DateTimeInterface
    {
        return $this->ordered_at;
    }

    public function setOrderedAt(\DateTimeInterface $ordered_at): static
    {
        $this->ordered_at = $ordered_at;

        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->total_price;
    }

    public function setTotalPrice(string $total_price): static
    {
        $this->total_price = $total_price;

        return $this;
    }
}
