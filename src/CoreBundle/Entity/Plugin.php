<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_USER\')'),
        new Put(security: 'is_granted(\'ROLE_ADMIN\')'),
        new Delete(security: 'is_granted(\'ROLE_ADMIN\')'),
        new GetCollection(),
        new Post(security: 'is_granted(\'ROLE_ADMIN\')'),
    ],
    normalizationContext: [
        'groups' => ['plugin:read', 'timestampable_created:read', 'timestampable_updated:read'],
    ],
)]
#[ORM\Table(name: 'plugin')]
#[ORM\Entity]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'title' => 'partial',
        'url' => 'exact',
        'version' => 'partial',
    ]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['title'])]
#[ApiFilter(BooleanFilter::class, properties: ['installed'])]
#[ApiFilter(BooleanFilter::class, properties: ['enabled'])]
class Plugin
{
    use TimestampableTypedEntity;
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[Assert\NotNull]
    #[Groups(['plugin:read', 'plugin:write'])]
    #[ORM\ManyToOne(targetEntity: AccessUrl::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected AccessUrl $url;
    #[Assert\NotBlank]
    #[Groups(['plugin:read', 'plugin:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    protected string $title;
    #[Groups(['plugin:read', 'plugin:write'])]
    #[ORM\Column(name: 'installed', type: 'boolean', nullable: false)]
    protected bool $installed;
    #[Groups(['plugin:read', 'plugin:write'])]
    #[ORM\Column(name: 'enabled', type: 'boolean', nullable: false)]
    protected bool $enabled;
    #[Groups(['plugin:read', 'plugin:write'])]
    #[ORM\Column(name: 'version', type: 'string', length: 10)]
    protected string $version;
    public function __construct()
    {
        $this->enabled = false;
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
    public function isInstalled(): bool
    {
        return $this->installed;
    }
    public function setInstalled(bool $installed): self
    {
        $this->installed = $installed;

        return $this;
    }
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
    public function getVersion(): string
    {
        return $this->version;
    }
    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }
    public function getUrl(): AccessUrl
    {
        return $this->url;
    }
    public function setUrl(AccessUrl $url): self
    {
        $this->url = $url;

        return $this;
    }
}
