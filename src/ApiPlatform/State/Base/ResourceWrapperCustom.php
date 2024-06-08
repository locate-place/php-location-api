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

namespace App\ApiPlatform\State\Base;

use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\Wrapper\ResourceWrapper;

/**
 * Class ResourceWrapperCustom
 *
 *  The ResourceWrapper for BaseResourceWrapperProvider to wrap it with additional API specific wrapper information:
 *
 *  - data resource
 *  - given resource
 *  - valid state of request
 *  - date of request
 *  - time-taken for request
 *  - version of API
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-30)
 * @since 0.1.0 (2023-08-30) First version.
 */
class ResourceWrapperCustom extends ResourceWrapper
{
    /** @var array{full: string, short: string, url: string} $dataLicence */
    protected array $dataLicence;

    /** @var array<int|string, mixed> $schema */
    protected array $schema;

    private string $timeTaken;

    private string $memoryTaken;

    /** @var array<int|string, mixed> $performance */
    private array $performance;

    /** @var array<int|string, mixed> $results */
    private array $results;

    /** @var array<int|string, array<string, mixed>|bool|int|string|null> */
    private array $given;

    /**
     * @return array{full: string, short: string, url: string}
     */
    public function getDataLicence(): array
    {
        return $this->dataLicence;
    }

    /**
     * @param array{full: string, short: string, url: string} $dataLicence
     * @return self
     */
    public function setDataLicence(array $dataLicence): self
    {
        $this->dataLicence = $dataLicence;

        return $this;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * @param array<int|string, mixed> $schema
     * @return self
     */
    public function setSchema(array $schema): self
    {
        $this->schema = $schema;

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
    public function getMemoryTaken(): string
    {
        return $this->memoryTaken;
    }

    /**
     * @param string $memoryTaken
     * @return self
     */
    public function setMemoryTaken(string $memoryTaken): self
    {
        $this->memoryTaken = $memoryTaken;

        return $this;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getPerformance(): array
    {
        return $this->performance;
    }

    /**
     * @param array<int|string, mixed> $performance
     * @return self
     */
    public function setPerformance(array $performance): self
    {
        $this->performance = $performance;

        return $this;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param array<int|string, mixed> $results
     * @return self
     */
    public function setResults(array $results): self
    {
        $this->results = $results;

        return $this;
    }

    /**
     * @return array<int|string, array<string, mixed>|bool|int|string|null>
     */
    public function getGiven(): array
    {
        return $this->given;
    }

    /**
     * @param array<int|string, array<string, mixed>|bool|int|string|null> $given
     * @return self
     */
    public function setGiven(array $given): self
    {
        $this->given = $given;

        return $this;
    }
}
