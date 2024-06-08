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
use App\Repository\ApiKeyCreditsDayRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class ApiKeyCreditsDay
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-08)
 * @since 0.1.0 (2024-06-08) First version.
 */
#[ORM\Entity(repositoryClass: ApiKeyCreditsDayRepository::class)]
#[UniqueEntity(
    fields: ['api_key_id', 'day'],
    message: 'The api key and day combination is already used with this ApiKeyCreditsDay entity.',
    errorPath: 'day'
)]
#[ORM\UniqueConstraint(columns: ['api_key_id', 'day'])]
#[ORM\Index(columns: ['api_key_id', 'day'])]
#[ORM\HasLifecycleCallbacks]
class ApiKeyCreditsDay
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'apiKeyCreditsDays')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ApiKey $apiKey = null;

    #[ORM\Column]
    private ?int $creditsUsed = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $day = null;

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
     * @return \DateTimeImmutable|null
     */
    public function getDay(): ?\DateTimeImmutable
    {
        return $this->day;
    }

    /**
     * @param \DateTimeImmutable $day
     * @return $this
     */
    public function setDay(\DateTimeImmutable $day): static
    {
        $this->day = $day;

        return $this;
    }
}
