<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Exception\EntityMissingAssignedId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\Index(columns: ['mastodon_id'], name: 'mastodon_id_idx')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    protected Uuid|string|null $id = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $mastodonId = null;

    #[ORM\OneToOne(inversedBy: 'owner', cascade: ['persist', 'remove'])]
    private ?AccessToken $MastodonAccessToken = null;

    /**
     * @throws EntityMissingAssignedId
     */
    public function getId(): Uuid|string
    {
        if (null === $this->id) {
            throw new EntityMissingAssignedId();
        }

        return $this->id;
    }

    public function setId(Uuid $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->getUsername();
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getMastodonId(): ?string
    {
        return $this->mastodonId;
    }

    public function setMastodonId(string $mastodonId): self
    {
        $this->mastodonId = $mastodonId;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getMastodonAccessToken(): ?AccessToken
    {
        return $this->MastodonAccessToken;
    }

    public function setMastodonAccessToken(?AccessToken $MastodonAccessToken): self
    {
        $this->MastodonAccessToken = $MastodonAccessToken;

        return $this;
    }
}
