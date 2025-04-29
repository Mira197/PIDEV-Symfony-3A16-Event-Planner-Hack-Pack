<?php

namespace App\Entity;

use App\Repository\FidelityPointRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FidelityPointRepository::class)]
class FidelityPoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id_user", nullable: false, onDelete: "CASCADE")]
    private ?User $user = null;

    #[ORM\Column(type: 'integer')]
    private int $points;

    #[ORM\Column(type: 'string', length: 10)]
    private string $type; // 'earned' | 'used'

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $reason = null;

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
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

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }
}
