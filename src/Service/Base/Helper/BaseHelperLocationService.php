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

namespace App\Service\Base\Helper;

use App\ApiPlatform\Resource\Location;
use App\Entity\Location as LocationEntity;
use App\Repository\LocationRepository;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpCoordinate\Coordinate;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class BaseHelperLocationService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 */
abstract class BaseHelperLocationService
{
    protected const DEBUG_LIMIT = 5;

    protected const DEBUG_CAPTION = '%-9s | %-6s | %-10s | %-2s | %-11s | %-8s | %-8s | %-8s | %-8s | %-20s | %s';

    protected const DEBUG_CONTENT = '%9s | %-6s | %10s | %-2s | %11s | %8s | %8s | %8s | %8s | %-20s | %s';

    protected ?string $error = null;

    protected bool $debug = false;

    protected float $timeStart;

    protected Coordinate $coordinate;

    protected LocationEntity|null $district = null;

    protected LocationEntity|null $city = null;

    protected LocationEntity|null $state = null;

    protected LocationEntity|null $country = null;

    protected OutputInterface $output;

    protected int $debugLimit = self::DEBUG_LIMIT;

    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     * @param LocationRepository $locationRepository
     * @param TranslatorInterface $translator
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected LocationRepository $locationRepository,
        protected TranslatorInterface $translator
    )
    {
        $this->setTimeStart(microtime(true));
    }

    /**
     * Gets an error of this resource.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Checks if an error occurred.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return is_string($this->error);
    }

    /**
     * Sets an error of this resource.
     *
     * @param string|null $error
     * @return self
     */
    protected function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Returns an empty Location entity.
     *
     * @param int|null $geonameId
     * @return Location
     */
    protected function getEmptyLocation(int|null $geonameId = null): Location
    {
        $location = new Location();

        if (!is_null($geonameId)) {
            $location->setGeonameId($geonameId);
        }

        return $location;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Sets debug mode.
     *
     * @param bool $debug
     * @param OutputInterface $output
     * @param int $debugLimit
     * @return self
     */
    public function setDebug(bool $debug, OutputInterface $output, int $debugLimit = self::DEBUG_LIMIT): self
    {
        $this->debug = $debug;
        $this->output = $output;
        $this->debugLimit = $debugLimit;

        return $this;
    }

    /**
     * @return Coordinate
     */
    public function getCoordinate(): Coordinate
    {
        return $this->coordinate;
    }

    /**
     * @param Coordinate $coordinate
     * @return self
     */
    public function setCoordinate(Coordinate $coordinate): self
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * @return float
     */
    public function getTimeStart(): float
    {
        return $this->timeStart;
    }

    /**
     * @param float $timeStart
     * @return self
     */
    public function setTimeStart(float $timeStart): self
    {
        $this->timeStart = $timeStart;

        return $this;
    }

    /**
     * @return LocationEntity|null
     */
    public function getDistrict(): ?LocationEntity
    {
        return $this->district;
    }

    /**
     * @param LocationEntity|null $district
     * @return self
     */
    public function setDistrict(?LocationEntity $district): self
    {
        $this->district = $district;

        return $this;
    }

    /**
     * @return LocationEntity|null
     */
    public function getCity(): ?LocationEntity
    {
        return $this->city;
    }

    /**
     * @param LocationEntity|null $city
     * @return self
     */
    public function setCity(?LocationEntity $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return LocationEntity|null
     */
    public function getState(): ?LocationEntity
    {
        return $this->state;
    }

    /**
     * @param LocationEntity|null $state
     * @return self
     */
    public function setState(?LocationEntity $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return LocationEntity|null
     */
    public function getCountry(): ?LocationEntity
    {
        return $this->country;
    }

    /**
     * @param LocationEntity|null $country
     * @return self
     */
    public function setCountry(?LocationEntity $country): self
    {
        $this->country = $country;

        return $this;
    }
}
