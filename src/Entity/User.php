<?php

namespace App\Entity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Repository\UserRepository;




#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]

#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
  
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_user = null;

    public function getId_user(): ?int
    {
        return $this->id_user;
    }

    public function setId_user(int $id_user): self
    {
        $this->id_user = $id_user;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le nom ne peut pas être vide.')]
    private ?string $last_name = null;

    public function getLast_name(): ?string
    {
        return $this->last_name;
    }

    public function setLast_name(string $last_name): self
    {
        $this->last_name = $last_name;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $blocked = false;
    
    #[ORM\Column(name: 'block_end_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $blockEndDate = null;


    public function isBlocked(): ?bool
{
    return $this->blocked;
}

public function setBlocked(?bool $blocked): self
{
    $this->blocked = $blocked;
    return $this;
}

public function getBlockEndDate(): ?\DateTimeInterface
{
    return $this->blockEndDate;
}

public function setBlockEndDate(?\DateTimeInterface $blockEndDate): self
{
    $this->blockEndDate = $blockEndDate;
    return $this;
}

 /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }



    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le prénom ne peut pas être vide.')]
    private ?string $first_name = null;

    public function getFirst_name(): ?string
    {
        return $this->first_name;
    }

    public function setFirst_name(string $first_name): self
    {
        $this->first_name = $first_name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'username ne peut pas être vide.')]
    private ?string $username = null;

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le mot de passe ne peut pas être vide.')]
    private ?string $password = null;

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $role = null;

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $address = null;

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    #[ORM\Column(name: 'imgPath', type: 'string', nullable: true)]
    private ?string $imgPath = null;

    public function getImgPath(): ?string
    {
        return $this->imgPath;
    }

    public function setImgPath(?string $imgPath): self
    {
        $this->imgPath = $imgPath;
        return $this;
    }

    #[ORM\Column(name: 'email', type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: "L'email ne peut pas être vide.")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas une adresse valide.")]
 
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(name: 'numTel', type: 'string', nullable: false)]

    #[Assert\NotBlank(message: "Le numéro de téléphone ne peut pas être vide.")]
    #[Assert\Length(
        min: 8,
        max: 8,
        exactMessage: "Le numéro de téléphone '{{ value }}' doit contenir exactement 8 chiffres."
    )]
    

    private ?string $numTel = null;


    public function getNumTel(): ?string
    {
        return $this->numTel;
    }

    public function setNumTel(string $numTel): self
    {
        $this->numTel = $numTel;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Cart::class, mappedBy: 'user')]
    private Collection $carts;

    /**
     * @return Collection<int, Cart>
     */
    public function getCarts(): Collection
    {
        if (!$this->carts instanceof Collection) {
            $this->carts = new ArrayCollection();
        }
        return $this->carts;
    }

    public function addCart(Cart $cart): self
    {
        if (!$this->getCarts()->contains($cart)) {
            $this->getCarts()->add($cart);
        }
        return $this;
    }

    public function removeCart(Cart $cart): self
    {
        $this->getCarts()->removeElement($cart);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user')]
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

    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'user')]
    private Collection $events;

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        if (!$this->events instanceof Collection) {
            $this->events = new ArrayCollection();
        }
        return $this->events;
    }

    public function addEvent(Event $event): self
    {
        if (!$this->getEvents()->contains($event)) {
            $this->getEvents()->add($event);
        }
        return $this;
    }

    public function removeEvent(Event $event): self
    {
        $this->getEvents()->removeElement($event);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private Collection $notifications;

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        if (!$this->notifications instanceof Collection) {
            $this->notifications = new ArrayCollection();
        }
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->getNotifications()->contains($notification)) {
            $this->getNotifications()->add($notification);
        }
        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        $this->getNotifications()->removeElement($notification);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'user')]
    private Collection $orders;

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        if (!$this->orders instanceof Collection) {
            $this->orders = new ArrayCollection();
        }
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->getOrders()->contains($order)) {
            $this->getOrders()->add($order);
        }
        return $this;
    }

    public function removeOrder(Order $order): self
    {
        $this->getOrders()->removeElement($order);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'user')]
    private Collection $products;

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        if (!$this->products instanceof Collection) {
            $this->products = new ArrayCollection();
        }
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

    #[ORM\OneToMany(targetEntity: Publication::class, mappedBy: 'user')]
    private Collection $publications;

    /**
     * @return Collection<int, Publication>
     */
    public function getPublications(): Collection
    {
        if (!$this->publications instanceof Collection) {
            $this->publications = new ArrayCollection();
        }
        return $this->publications;
    }

    public function addPublication(Publication $publication): self
    {
        if (!$this->getPublications()->contains($publication)) {
            $this->getPublications()->add($publication);
        }
        return $this;
    }

    public function removePublication(Publication $publication): self
    {
        $this->getPublications()->removeElement($publication);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Report::class, mappedBy: 'user')]
    private Collection $reports;

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

    #[ORM\OneToMany(targetEntity: Stock::class, mappedBy: 'user')]
    private Collection $stocks;

    public function __construct()
    {
        $this->carts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->publications = new ArrayCollection();
        $this->reports = new ArrayCollection();
        $this->stocks = new ArrayCollection();
    }

    /**
     * @return Collection<int, Stock>
     */
    public function getStocks(): Collection
    {
        if (!$this->stocks instanceof Collection) {
            $this->stocks = new ArrayCollection();
        }
        return $this->stocks;
    }

    public function addStock(Stock $stock): self
    {
        if (!$this->getStocks()->contains($stock)) {
            $this->getStocks()->add($stock);
        }
        return $this;
    }

    public function removeStock(Stock $stock): self
    {
        $this->getStocks()->removeElement($stock);
        return $this;
    }

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }
    
    public function getId(): ?int
    {
    return $this->id_user;
    }




  /*
  
    public function getBase64Image(): ?string
    {
        if (!$this->image) {
            return null;
        }
    
        if (is_resource($this->image)) {
            return base64_encode(stream_get_contents($this->image));
        }
    
        return base64_encode($this->image);
    }*/ 


    public function getRoles(): array
    {
        return $this->role ? [$this->role] : [];
    }
    


    public function getSalt()
    {
        // Vous n'avez pas besoin de sel car bcrypt gère cela pour vous
        return null ;
    }

    public function eraseCredentials()
    {
        // Supprimer les données sensibles de l'utilisateur
        // Cette méthode est nécessaire pour effacer les mots de passe en texte brut
    }

}