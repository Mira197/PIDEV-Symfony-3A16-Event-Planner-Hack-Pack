<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PublicationRepository;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\Table(name: 'publications')]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $publication_id = null;

    public function getPublication_id(): ?int
    {
        return $this->publication_id;
    }

    public function setPublication_id(int $publication_id): self
    {
        $this->publication_id = $publication_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $title = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }
    #[ORM\Column(type: 'blob', nullable: true)]
    private $image;

    public function getImage()
    {
    return $this->image;
    }

    public function setImage($image): self
    {
    $this->image = $image;
    return $this;
    }

    public function getBase64Image(): ?string
    {
        if (!$this->image) {
            return null;
        }
    
        if (is_resource($this->image)) {
            return base64_encode(stream_get_contents($this->image));
        }
    
        return base64_encode($this->image);
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

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $publication_date = null;

    public function getPublication_date(): ?\DateTimeInterface
    {
        return $this->publication_date;
    }

    public function setPublication_date(\DateTimeInterface $publication_date): self
    {
        $this->publication_date = $publication_date;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'publications')]
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
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'publication')]
    private Collection $comments;

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        if (!$this->comments instanceof Collection) {
            $this->comments = new ArrayCollection();
        }
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->getComments()->contains($comment)) {
            $this->getComments()->add($comment);
        }
        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        $this->getComments()->removeElement($comment);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Report::class, mappedBy: 'publication')]
    private Collection $reports;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->reports = new ArrayCollection();
    }

    /**
     * @return Collection<int, Report>
     */
    public function getReports(): Collection
    {
        if (!$this->reports instanceof Collection) {
            $this->reports = new ArrayCollection();
        }
        return $this->reports;
    }

    public function addReport(Report $report): self
    {
        if (!$this->getReports()->contains($report)) {
            $this->getReports()->add($report);
        }
        return $this;
    }

    public function removeReport(Report $report): self
    {
        $this->getReports()->removeElement($report);
        return $this;
    }

    public function getPublicationId(): ?int
    {
        return $this->publication_id;
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

    public function getPublicationDate(): ?\DateTimeInterface
    {
        return $this->publication_date;
    }

    public function setPublicationDate(\DateTimeInterface $publication_date): static
    {
        $this->publication_date = $publication_date;

        return $this;
    }
    

}
