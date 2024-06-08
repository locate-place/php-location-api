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
use App\Repository\ApiRequestLogTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class ApiRequestLogType
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-08)
 * @since 0.1.0 (2024-06-08) First version.
 */
#[ORM\Entity(repositoryClass: ApiRequestLogTypeRepository::class)]
#[UniqueEntity('type')]
#[ORM\UniqueConstraint(columns: ['type'])]
#[ORM\Index(columns: ['type'])]
#[ORM\HasLifecycleCallbacks]
class ApiRequestLogType
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    /**
     * @var Collection<int, ApiRequestLog>
     */
    #[ORM\OneToMany(targetEntity: ApiRequestLog::class, mappedBy: 'apiRequestLogType', orphanRemoval: true)]
    private Collection $apiRequestLogs;

    /**
     *
     */
    public function __construct()
    {
        $this->apiRequestLogs = new ArrayCollection();
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
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

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
            $apiRequestLog->setApiRequestLogType($this);
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
            if ($apiRequestLog->getApiRequestLogType() === $this) {
                $apiRequestLog->setApiRequestLogType(null);
            }
        }

        return $this;
    }
}
