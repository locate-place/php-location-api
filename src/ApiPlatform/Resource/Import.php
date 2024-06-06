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

namespace App\ApiPlatform\Resource;

use ApiPlatform\Metadata\GetCollection;
use App\ApiPlatform\OpenApiContext\Parameter;
use App\ApiPlatform\Route\ImportRoute;
use App\ApiPlatform\State\ImportProvider;
use DateTimeImmutable;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;

/**
 * Class Import
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-22)
 * @since 0.1.0 (2023-07-22) First version.
 */
#[GetCollection(
    openapiContext: [
        'summary' => ImportRoute::SUMMARY_GET_COLLECTION,
        'description' => ImportRoute::DESCRIPTION_GET_COLLECTION,
        'parameters' => [
            Parameter::FORMAT,
        ],
    ],
    paginationEnabled: false,
    provider: ImportProvider::class
)]
class Import extends BasePublicResource
{
    protected string $country;

    protected string $countryCode;

    private int $numberOfLocations;

    private int $numberOfAlternateNames;

    private string $path;

    private DateTimeImmutable $createdAt;

    private DateTimeImmutable $updatedAt;

    private int $executionTime;

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return self
     */
    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * @param string $countryCode
     * @return self
     */
    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfLocations(): int
    {
        return $this->numberOfLocations;
    }

    /**
     * @param int $numberOfLocations
     * @return self
     */
    public function setNumberOfLocations(int $numberOfLocations): self
    {
        $this->numberOfLocations = $numberOfLocations;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfAlternateNames(): int
    {
        return $this->numberOfAlternateNames;
    }

    /**
     * @param int $numberOfAlternateNames
     * @return self
     */
    public function setNumberOfAlternateNames(int $numberOfAlternateNames): self
    {
        $this->numberOfAlternateNames = $numberOfAlternateNames;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return self
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @param DateTimeImmutable $createdAt
     * @return self
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTimeImmutable $updatedAt
     * @return self
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getExecutionTime(): int
    {
        return $this->executionTime;
    }

    /**
     * @param int $executionTime
     * @return self
     */
    public function setExecutionTime(int $executionTime): self
    {
        $this->executionTime = $executionTime;

        return $this;
    }
}
