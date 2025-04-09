<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CodePromoRepository;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: CodePromoRepository::class)]
#[ORM\Table(name: 'code_promo')]
class CodePromo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Promo code is required.")]
    #[Assert\Length(
        min: 3,
        max: 20,
        minMessage: "The code must be at least {{ limit }} characters long.",
        maxMessage: "The code must not exceed {{ limit }} characters."
    )]
    private ?string $code_promo = null;

    public function getCode_promo(): ?string
    {
        return $this->code_promo;
    }

    public function setCode_promo(string $code_promo): self
    {
        $this->code_promo = $code_promo;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    #[Assert\NotNull(message: "Discount percentage is required.")]
    #[Assert\Type(type: 'numeric', message: "The discount must be a number.")]
    #[Assert\Range(
        min: 1,
        max: 100,
        notInRangeMessage: "The discount must be between {{ min }}% and {{ max }}%."
    )]
    private ?float $pourcentage = null;

    public function getPourcentage(): ?float
    {
        return $this->pourcentage;
    }

    public function setPourcentage(float $pourcentage): self
    {
        $this->pourcentage = $pourcentage;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotNull(message: "Creation date is required.")]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $date_creation = null;

    public function getDate_creation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDate_creation(\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotNull(message: "Expiration date is required.")]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\GreaterThan("today", message: "The expiration date must be in the future.")]
    private ?\DateTimeInterface $date_expiration = null;

    public function getDate_expiration(): ?\DateTimeInterface
    {
        return $this->date_expiration;
    }

    public function setDate_expiration(\DateTimeInterface $date_expiration): self
    {
        $this->date_expiration = $date_expiration;
        return $this;
    }

    public function getCodePromo(): ?string
    {
        return $this->code_promo;
    }

    public function setCodePromo(string $code_promo): static
    {
        $this->code_promo = $code_promo;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeInterface $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->date_expiration;
    }

    public function setDateExpiration(\DateTimeInterface $date_expiration): static
    {
        $this->date_expiration = $date_expiration;

        return $this;
    }

}
