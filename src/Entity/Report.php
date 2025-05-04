<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;


use App\Repository\ReportRepository;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'reports')]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $report_id = null;

    public function getReport_id(): ?int
    {
        return $this->report_id;
    }

    public function setReport_id(int $report_id): self
    {
        $this->report_id = $report_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'The reason is required.')]
    #[Assert\Regex(
        pattern: '/^[^\d]+$/',
        message: 'The reason cannot contain numbers.'
    )]
    private ?string $reason = null;

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: 'The description is required.')]
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

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $report_date = null;

    public function getReport_date(): ?\DateTimeInterface
    {
        return $this->report_date;
    }

    public function setReport_date(\DateTimeInterface $report_date): self
    {
        $this->report_date = $report_date;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'The status is required.')]
    #[Assert\Choice(
        choices: ['Pending', 'Verified', 'Rejected'],
        message: 'Choose a valid status: Pending, Verified, or Rejected.'
    )]
    private ?string $status = 'Pending';

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reports')]
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

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'reports')]
    #[ORM\JoinColumn(name: 'publication_id', referencedColumnName: 'publication_id')]
    private ?Publication $publication = null;

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): self
    {
        $this->publication = $publication;
        return $this;
    }

    public function getReportId(): ?int
    {
        return $this->report_id;
    }

    public function getReportDate(): ?\DateTimeInterface
    {
        return $this->report_date;
    }

    public function setReportDate(\DateTimeInterface $report_date): static
    {
        $this->report_date = $report_date;

        return $this;
    }

}