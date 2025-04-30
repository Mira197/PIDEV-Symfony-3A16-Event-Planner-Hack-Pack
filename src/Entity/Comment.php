<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;


use App\Repository\CommentRepository;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comments')]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $comment_id = null;

    public function getComment_id(): ?int
    {
        return $this->comment_id;
    }

    public function setComment_id(int $comment_id): self
    {
        $this->comment_id = $comment_id;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    #[Assert\NotBlank(message: 'The comment cannot be empty.')]
    #[Assert\Length(
        min: 5,
        max: 1000,
        minMessage: 'The comment must be at least {{ limit }} characters.',
        maxMessage: 'The comment cannot be longer than {{ limit }} characters.'
    )]
    private ?string $content = null;

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $comment_date = null;

    public function getComment_date(): ?\DateTimeInterface
    {
        return $this->comment_date;
    }

    public function setComment_date(\DateTimeInterface $comment_date): self
    {
        $this->comment_date = $comment_date;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'comments')]
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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'comments')]
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

    public function getCommentId(): ?int
    {
        return $this->comment_id;
    }

    public function getCommentDate(): ?\DateTimeInterface
    {
        return $this->comment_date;
    }

    public function setCommentDate(\DateTimeInterface $comment_date): static
    {
        $this->comment_date = $comment_date;

        return $this;
    }

}