<?php

namespace App\Entity;

use App\Repository\CartProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


#[ORM\Entity(repositoryClass: CartProductRepository::class)]
#[ORM\Table(name: "cart_product", schema: "hackpack6")]
#[ORM\HasLifecycleCallbacks]
#[Assert\Callback('validateStock')]
class CartProduct
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Cart::class)]
    #[ORM\JoinColumn(name: "cart_id", referencedColumnName: "cart_id")]
    private Cart $cart;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: "product_id", referencedColumnName: "product_id")]
    private Product $product;

    #[ORM\Column(type: "integer")]
    #[Assert\NotBlank(message: "Quantity is required.")]
    #[Assert\Positive(message: "Quantity must be greater than zero.")]
    private int $quantity = 0;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    #[Assert\NotBlank(message: "Total price is required.")]
    #[Assert\GreaterThanOrEqual(value: 0, message: "Total price cannot be negative.")]
    private string $total_price;

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function setCart(Cart $cart): self
    {
        $this->cart = $cart;
        return $this;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;
        if ($this->quantity) {
            $this->total_price = bcmul((string) $product->getPrice(), (string) $this->quantity, 2);
        }
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        if ($this->product) {
            $this->total_price = bcmul((string) $this->product->getPrice(), (string) $quantity, 2);
        }
        return $this;
    }

    public function getTotalPrice(): string
    {
        return $this->total_price;
    }

    public function setTotalPrice(string $total_price): self
    {
        $this->total_price = $total_price;
        return $this;
    }

    #[Assert\Callback]
public function validateStock(ExecutionContextInterface $context): void
{
    if ($this->product && $this->quantity > $this->product->getStock()->getAvailableQuantity()) {
        $context->buildViolation('Only {{ limit }} item(s) in stock.')
            ->setParameter('{{ limit }}', $this->product->getStock()->getAvailableQuantity())
            ->atPath('quantity')
            ->addViolation();
    }
}
    
}
