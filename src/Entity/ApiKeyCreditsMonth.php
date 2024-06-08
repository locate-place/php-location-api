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
use App\Repository\ApiKeyCreditsMonthRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class ApiKeyCreditsMonth
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-08)
 * @since 0.1.0 (2024-06-08) First version.
 */
#[ORM\Entity(repositoryClass: ApiKeyCreditsMonthRepository::class)]
#[UniqueEntity(
    fields: ['api_key_id', 'month'],
    message: 'The api key and month combination is already used with this ApiKeyCreditsMonth entity.',
    errorPath: 'month'
)]
#[ORM\UniqueConstraint(columns: ['api_key_id', 'month'])]
#[ORM\Index(columns: ['api_key_id', 'month'])]
#[ORM\HasLifecycleCallbacks]
class ApiKeyCreditsMonth
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'apiKeyCreditsMonths')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ApiKey $apiKey = null;

    #[ORM\Column]
    private ?int $creditsUsed = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $month = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return ApiKey|null
     */
    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }

    /**
     * @param ApiKey|null $apiKey
     * @return $this
     */
    public function setApiKey(?ApiKey $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreditsUsed(): ?int
    {
        return $this->creditsUsed;
    }

    /**
     * @param int $creditsUsed
     * @return $this
     */
    public function setCreditsUsed(int $creditsUsed): static
    {
        $this->creditsUsed = $creditsUsed;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getMonth(): ?\DateTimeInterface
    {
        return $this->month;
    }

    /**
     * @param \DateTimeInterface $month
     * @return $this
     */
    public function setMonth(\DateTimeInterface $month): static
    {
        $this->month = $month;

        return $this;
    }
}
