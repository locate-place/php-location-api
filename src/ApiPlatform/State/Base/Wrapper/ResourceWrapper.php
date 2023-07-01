<?php

/*
 * This file is part of the twelvepics-com/php-location-api project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\ApiPlatform\State\Base\Wrapper;

use DateTimeImmutable;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;

/**
 * Class ResourceWrapper
 *
 * The ResourceWrapper for BaseResourceWrapperProvider to wrap it with additional API specific wrapper information:
 *
 * - data resource
 * - given resource
 * - valid state of request
 * - date of request
 * - time-taken for request
 * - version of API
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 */
class ResourceWrapper
{
    /** @var BasePublicResource|BasePublicResource[] $data */
    private BasePublicResource|array $data;

    /** @var array<int|string, int|string|bool> */
    private array $given;

    private bool $valid = true;

    private ?string $error = null;

    /** @var array<int|string, mixed>|null $validationDetails */
    private ?array $validationDetails = null;

    /** @var array<int|string, mixed>|null $enrichmentDetails */
    private ?array $enrichmentDetails = null;

    private DateTimeImmutable $date;

    private string $timeTaken;

    private string $version;

    /**
     * Gets the BaseResource entity or collection (data).
     *
     * @return BasePublicResource|BasePublicResource[]
     */
    public function getData(): BasePublicResource|array
    {
        return $this->data;
    }

    /**
     * Sets the BaseResource entity or collection (data).
     *
     * @param BasePublicResource|BasePublicResource[] $data
     * @return self
     */
    public function setData(BasePublicResource|array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array<int|string, int|string|bool>
     */
    public function getGiven(): array
    {
        return $this->given;
    }

    /**
     * @param array<int|string, int|string|bool> $given
     * @return self
     */
    public function setGiven(array $given): self
    {
        $this->given = $given;

        return $this;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     * @return self
     */
    public function setValid(bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @param string $error
     * @return self
     */
    public function setError(string $error): self
    {
        $this->error = $error;

        return $this;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function getValidationDetails(): ?array
    {
        return $this->validationDetails;
    }

    /**
     * @param array<int|string, mixed> $validationDetails
     * @return self
     */
    public function setValidationDetails(array $validationDetails): self
    {
        $this->validationDetails = $validationDetails;

        return $this;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function getEnrichmentDetails(): ?array
    {
        return $this->enrichmentDetails;
    }

    /**
     * @param array<int|string, mixed> $enrichmentDetails
     * @return self
     */
    public function setEnrichmentDetails(?array $enrichmentDetails): self
    {
        $this->enrichmentDetails = $enrichmentDetails;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * @param DateTimeImmutable $date
     * @return self
     */
    public function setDate(DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimeTaken(): string
    {
        return $this->timeTaken;
    }

    /**
     * @param string $timeTaken
     * @return self
     */
    public function setTimeTaken(string $timeTaken): self
    {
        $this->timeTaken = $timeTaken;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return self
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }
}
