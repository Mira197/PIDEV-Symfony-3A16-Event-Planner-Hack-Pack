<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'gift_card')]
class GiftCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private string $code;

    #[ORM\Column]
    private string $pin;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $balance;

    #[ORM\Column(type: 'boolean')]
    private bool $isUsed = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeInterface $usedAt = null;

    // === GETTERS & SETTERS ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getPin(): string
    {
        return $this->pin;
    }

    public function setPin(string $pin): self
    {
        $this->pin = $pin;
        return $this;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): self
    {
        $this->balance = $balance;
        return $this;
    }

    // Méthode renommer pour respecter les conventions de Symfony
    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    public function setIsUsed(bool $isUsed): self
    {
        $this->isUsed = $isUsed;
        return $this;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }
    

    public function setUsedAt(?\DateTimeInterface $usedAt): self
    {
        $this->usedAt = $usedAt;
        return $this;
    }
}
