<?php

namespace App\Entity;

use App\Repository\AccessTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Exception\EntityMissingAssignedId;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use League\OAuth2\Client\Token\AccessTokenInterface;
use League\OAuth2\Client\Token\ResourceOwnerAccessTokenInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AccessTokenRepository::class)]
class AccessToken
{

    public const TYPE_MASTODON = 'mastodon';
    public const TYPE_PATREON = 'patreon';

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    protected Uuid|string|null $id = null;

    #[ORM\OneToOne(mappedBy: 'MastodonAccessToken', cascade: ['persist', 'remove'])]
    private ?User $owner = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $token = null;

    #[ORM\Column(nullable: true)]
    private ?int $expires = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $refreshToken = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $values = [];

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

    public function getOwner(): User
    {
        if ($this->owner === null) {
            throw new \LogicException('Owner cannot be null');
        }
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        // unset the owning side of the relation if necessary
        if ($owner === null && $this->owner !== null) {
            $this->owner->setMastodonAccessToken(null);
        }

        // set the owning side of the relation if necessary
        if ($owner !== null && $owner->getMastodonAccessToken() !== $this) {
            $owner->setMastodonAccessToken($this);
        }

        $this->owner = $owner;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $accessToken): self
    {
        $this->token = $accessToken;

        return $this;
    }

    public function getExpires(): ?int
    {
        return $this->expires;
    }

    public function setExpires(?int $expires): self
    {
        $this->expires = $expires;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function getOauth2Token(): \League\OAuth2\Client\Token\AccessToken
    {
        return new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => $this->getToken(),
            'resource_owner_id' => match ($this->getType()) {
                self::TYPE_MASTODON => $this->getOwner()->getMastodonId(),
                default => throw new Exception('Invalid token type')
            },
            'expires' => $this->getExpires(),
            'refresh_token' => $this->getRefreshToken(),
        ]);
    }
}
