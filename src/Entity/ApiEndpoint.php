<?php

/*
 * This file is part of the locate-place/php-location-api project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\TimestampsTrait;
use App\Repository\ApiEndpointRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class ApiEndpoint
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-08)
 * @since 0.1.0 (2024-06-08) First version.
 */
#[ORM\Entity(repositoryClass: ApiEndpointRepository::class)]
#[UniqueEntity(
    fields: ['endpoint', 'method'],
    message: 'The endpoint and method combination is already used with this ApiEndpoint entity.',
    errorPath: 'endpoint',
    ignoreNull: ['endpoint', 'method']
)]
#[ORM\UniqueConstraint(columns: ['endpoint', 'method'])]
#[ORM\Index(columns: ['endpoint', 'method'])]
#[ORM\HasLifecycleCallbacks]
class ApiEndpoint
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $endpoint = null;

    #[ORM\Column(length: 14, nullable: true)]
    private ?string $method = null;

    #[ORM\Column(nullable: true)]
    private ?int $credits = null;

    /**
     * @var Collection<int, ApiRequestLog>
     */
    #[ORM\OneToMany(targetEntity: ApiRequestLog::class, mappedBy: 'apiEndpoint', orphanRemoval: true)]
    private Collection $apiRequestLogs;

    public function __construct()
    {
        $this->apiRequestLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): static
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(?int $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * @return Collection<int, ApiRequestLog>
     */
    public function getApiRequestLogs(): Collection
    {
        return $this->apiRequestLogs;
    }

    public function addApiRequestLog(ApiRequestLog $apiRequestLog): static
    {
        if (!$this->apiRequestLogs->contains($apiRequestLog)) {
            $this->apiRequestLogs->add($apiRequestLog);
            $apiRequestLog->setApiEndpoint($this);
        }

        return $this;
    }

    public function removeApiRequestLog(ApiRequestLog $apiRequestLog): static
    {
        if ($this->apiRequestLogs->removeElement($apiRequestLog)) {
            // set the owning side to null (unless already changed)
            if ($apiRequestLog->getApiEndpoint() === $this) {
                $apiRequestLog->setApiEndpoint(null);
            }
        }

        return $this;
    }
}
