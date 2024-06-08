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
use App\Repository\ApiKeyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class ApiKey
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-08)
 * @since 0.1.0 (2024-06-08) First version.
 */
#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
#[UniqueEntity('api_key')]
#[ORM\UniqueConstraint(columns: ['api_key'])]
#[ORM\Index(columns: ['api_key'])]
#[ORM\HasLifecycleCallbacks]
class ApiKey
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true)]
    private ?string $apiKey = null;

    #[ORM\Column]
    private ?bool $isPublic = null;

    #[ORM\Column]
    private ?bool $hasIpLimit = null;

    #[ORM\Column]
    private ?bool $hasCredentialLimit = null;

    #[ORM\Column(nullable: true)]
    private ?int $limitsPerMinute = null;

    #[ORM\Column(nullable: true)]
    private ?int $limitsPerHour = null;

    #[ORM\Column(nullable: true)]
    private ?int $creditsPerDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $creditsPerMonth = null;

    /** @var Collection<int, ApiRequestLog> */
    #[ORM\OneToMany(targetEntity: ApiRequestLog::class, mappedBy: 'apiKey', orphanRemoval: true)]
    private Collection $apiRequestLogs;

    /** @var Collection<int, ApiKeyCreditsDay> */
    #[ORM\OneToMany(targetEntity: ApiKeyCreditsDay::class, mappedBy: 'apiKey', orphanRemoval: true)]
    private Collection $apiKeyCreditsDays;

    /** @var Collection<int, ApiKeyCreditsMonth> */
    #[ORM\OneToMany(targetEntity: ApiKeyCreditsMonth::class, mappedBy: 'apiKey', orphanRemoval: true)]
    private Collection $apiKeyCreditsMonths;

    /**
     *
     */
    public function __construct()
    {
        $this->apiRequestLogs = new ArrayCollection();
        $this->apiKeyCreditsDays = new ArrayCollection();
        $this->apiKeyCreditsMonths = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     * @return $this
     */
    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isPublic(): ?bool
    {
        return $this->isPublic;
    }

    /**
     * @param bool $isPublic
     * @return $this
     */
    public function setPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function hasIpLimit(): ?bool
    {
        return $this->hasIpLimit;
    }

    /**
     * @param bool $hasIpLimit
     * @return $this
     */
    public function setHasIpLimit(bool $hasIpLimit): static
    {
        $this->hasIpLimit = $hasIpLimit;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function hasCredentialLimit(): ?bool
    {
        return $this->hasCredentialLimit;
    }

    /**
     * @param bool $hasCredentialLimit
     * @return $this
     */
    public function setHasCredentialLimit(bool $hasCredentialLimit): static
    {
        $this->hasCredentialLimit = $hasCredentialLimit;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLimitsPerMinute(): ?int
    {
        return $this->limitsPerMinute;
    }

    /**
     * @param int|null $limitsPerMinute
     * @return $this
     */
    public function setLimitsPerMinute(?int $limitsPerMinute): static
    {
        $this->limitsPerMinute = $limitsPerMinute;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLimitsPerHour(): ?int
    {
        return $this->limitsPerHour;
    }

    /**
     * @param int|null $limitsPerHour
     * @return $this
     */
    public function setLimitsPerHour(?int $limitsPerHour): static
    {
        $this->limitsPerHour = $limitsPerHour;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreditsPerDay(): ?int
    {
        return $this->creditsPerDay;
    }

    /**
     * @param int|null $creditsPerDay
     * @return $this
     */
    public function setCreditsPerDay(?int $creditsPerDay): static
    {
        $this->creditsPerDay = $creditsPerDay;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreditsPerMonth(): ?int
    {
        return $this->creditsPerMonth;
    }

    /**
     * @param int|null $creditsPerMonth
     * @return $this
     */
    public function setCreditsPerMonth(?int $creditsPerMonth): static
    {
        $this->creditsPerMonth = $creditsPerMonth;

        return $this;
    }

    /**
     * @return Collection<int, ApiRequestLog>
     */
    public function getApiRequestLogs(): Collection
    {
        return $this->apiRequestLogs;
    }

    /**
     * @param ApiRequestLog $apiRequestLog
     * @return $this
     */
    public function addApiRequestLog(ApiRequestLog $apiRequestLog): static
    {
        if (!$this->apiRequestLogs->contains($apiRequestLog)) {
            $this->apiRequestLogs->add($apiRequestLog);
            $apiRequestLog->setApiKey($this);
        }

        return $this;
    }

    /**
     * @param ApiRequestLog $apiRequestLog
     * @return $this
     */
    public function removeApiRequestLog(ApiRequestLog $apiRequestLog): static
    {
        if ($this->apiRequestLogs->removeElement($apiRequestLog)) {
            // set the owning side to null (unless already changed)
            if ($apiRequestLog->getApiKey() === $this) {
                $apiRequestLog->setApiKey(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApiKeyCreditsDay>
     */
    public function getApiKeyCreditsDays(): Collection
    {
        return $this->apiKeyCreditsDays;
    }

    /**
     * @param ApiKeyCreditsDay $apiKeyCreditsDay
     * @return $this
     */
    public function addApiKeyCreditsDay(ApiKeyCreditsDay $apiKeyCreditsDay): static
    {
        if (!$this->apiKeyCreditsDays->contains($apiKeyCreditsDay)) {
            $this->apiKeyCreditsDays->add($apiKeyCreditsDay);
            $apiKeyCreditsDay->setApiKey($this);
        }

        return $this;
    }

    /**
     * @param ApiKeyCreditsDay $apiKeyCreditsDay
     * @return $this
     */
    public function removeApiKeyCreditsDay(ApiKeyCreditsDay $apiKeyCreditsDay): static
    {
        if ($this->apiKeyCreditsDays->removeElement($apiKeyCreditsDay)) {
            // set the owning side to null (unless already changed)
            if ($apiKeyCreditsDay->getApiKey() === $this) {
                $apiKeyCreditsDay->setApiKey(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApiKeyCreditsMonth>
     */
    public function getApiKeyCreditsMonths(): Collection
    {
        return $this->apiKeyCreditsMonths;
    }

    /**
     * @param ApiKeyCreditsMonth $apiKeyCreditsMonth
     * @return $this
     */
    public function addApiKeyCreditsMonth(ApiKeyCreditsMonth $apiKeyCreditsMonth): static
    {
        if (!$this->apiKeyCreditsMonths->contains($apiKeyCreditsMonth)) {
            $this->apiKeyCreditsMonths->add($apiKeyCreditsMonth);
            $apiKeyCreditsMonth->setApiKey($this);
        }

        return $this;
    }

    /**
     * @param ApiKeyCreditsMonth $apiKeyCreditsMonth
     * @return $this
     */
    public function removeApiKeyCreditsMonth(ApiKeyCreditsMonth $apiKeyCreditsMonth): static
    {
        if ($this->apiKeyCreditsMonths->removeElement($apiKeyCreditsMonth)) {
            // set the owning side to null (unless already changed)
            if ($apiKeyCreditsMonth->getApiKey() === $this) {
                $apiKeyCreditsMonth->setApiKey(null);
            }
        }

        return $this;
    }
}
