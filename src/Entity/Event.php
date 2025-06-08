<?php

namespace App\Entity;

use App\Repository\EventRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[Vich\Uploadable]
class Event
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid")]
    #[Assert\Uuid]
    #[Groups(['event:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\Blank]
    #[Groups(['event:read', 'event:write'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Assert\Blank]
    #[Groups(['event:read', 'event:write'])]
    private ?string $subtitle = null;

    #[Vich\UploadableField(mapping: 'event', fileNameProperty: 'imageName', size: 'imageSize')]
    private ?File $image = null;

    #[Groups(['event:read'])]
    #[ORM\Column(nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['event:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    #[Assert\Blank]
    #[Groups(['event:read'])]
    private ?string $location = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'attendee_events_id')]
    private Collection $attendee_users_id;

    #[ORM\Column]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['event:write'])]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'saved_events')]
    private Collection $saved_users;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->created_at = new DateTimeImmutable('now');
        $this->attendee_users_id = new ArrayCollection();
        $this->saved_users = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getImage(): ?File
    {
        return $this->image;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageSize(?int $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $image
     */
    public function setImage(?File $imageFile = null)
    {
        $this->image = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updated_at = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAttendeeUsersId(): Collection
    {
        return $this->attendee_users_id;
    }

    public function addAttendeeUsersId(User $attendeeUsersId): static
    {
        if (!$this->attendee_users_id->contains($attendeeUsersId)) {
            $this->attendee_users_id->add($attendeeUsersId);
        }

        return $this;
    }

    public function removeAttendeeUsersId(User $attendeeUsersId): static
    {
        $this->attendee_users_id->removeElement($attendeeUsersId);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getSavedUsers(): Collection
    {
        return $this->saved_users;
    }

    public function addSavedUser(User $savedUser): static
    {
        if (!$this->saved_users->contains($savedUser)) {
            $this->saved_users->add($savedUser);
            $savedUser->addSavedEvent($this);
        }

        return $this;
    }

    public function removeSavedUser(User $savedUser): static
    {
        if ($this->saved_users->removeElement($savedUser)) {
            $savedUser->removeSavedEvent($this);
        }

        return $this;
    }
}
