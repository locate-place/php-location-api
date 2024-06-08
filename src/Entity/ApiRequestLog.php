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
use App\Repository\ApiRequestLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ApiRequestLog
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-08)
 * @since 0.1.0 (2024-06-08) First version.
 */
#[ORM\Entity(repositoryClass: ApiRequestLogRepository::class)]
#[ORM\Index(columns: ['api_key_id'])]
#[ORM\Index(columns: ['api_key_id', 'created_at'])]
#[ORM\Index(columns: ['api_key_id', 'created_at', 'is_valid'])]
#[ORM\Index(columns: ['api_key_id', 'created_at', 'api_request_log_type_id'])]
#[ORM\Index(columns: ['api_key_id', 'api_request_log_type_id'])]
#[ORM\Index(columns: ['api_request_log_type_id', 'created_at'])]
#[ORM\HasLifecycleCallbacks]
class ApiRequestLog
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'apiRequestLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ApiKey $apiKey = null;

    #[ORM\ManyToOne(inversedBy: 'apiRequestLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ApiRequestLogType $apiRequestLogType = null;

    #[ORM\ManyToOne(inversedBy: 'apiRequestLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ApiEndpoint $apiEndpoint = null;

    #[ORM\Column(nullable: true)]
    private ?int $creditsUsed = null;

    #[ORM\Column(length: 45)]
    private ?string $ip = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $browser = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $referrer = null;

    #[ORM\Column]
    private ?bool $isValid = null;

    /** @var array<int|string, mixed> $given */
    #[ORM\Column(name: 'given', type: 'json')]
    private array $given = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error = null;

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
     * @return ApiRequestLogType|null
     */
    public function getApiRequestLogType(): ?ApiRequestLogType
    {
        return $this->apiRequestLogType;
    }

    /**
     * @param ApiRequestLogType|null $apiRequestLogType
     * @return $this
     */
    public function setApiRequestLogType(?ApiRequestLogType $apiRequestLogType): static
    {
        $this->apiRequestLogType = $apiRequestLogType;

        return $this;
    }

    /**
     * @return ApiEndpoint|null
     */
    public function getApiEndpoint(): ?ApiEndpoint
    {
        return $this->apiEndpoint;
    }

    /**
     * @param ApiEndpoint|null $apiEndpoint
     * @return $this
     */
    public function setApiEndpoint(?ApiEndpoint $apiEndpoint): static
    {
        $this->apiEndpoint = $apiEndpoint;

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
     * @param int|null $creditsUsed
     * @return $this
     */
    public function setCreditsUsed(int|null $creditsUsed): static
    {
        $this->creditsUsed = $creditsUsed;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return $this
     */
    public function setIp(string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    /**
     * @param string $browser
     * @return $this
     */
    public function setBrowser(string $browser): static
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReferrer(): ?string
    {
        return $this->referrer;
    }

    /**
     * @param string $referrer
     * @return $this
     */
    public function setReferrer(string $referrer): static
    {
        $this->referrer = $referrer;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isValid(): ?bool
    {
        return $this->isValid;
    }

    /**
     * @param bool $isValid
     * @return $this
     */
    public function setValid(bool $isValid): static
    {
        $this->isValid = $isValid;

        return $this;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getGiven(): array
    {
        return $this->given;
    }

    /**
     * @param array<int|string, mixed> $given
     * @return $this
     */
    public function setGiven(array $given): static
    {
        $this->given = $given;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): static
    {
        $this->error = $error;

        return $this;
    }
}
