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
use App\Repository\AlternateNameRepository;
use App\Repository\LocationRepository;
use App\Service\LocationContainer;
use App\Service\LocationServiceConfig;
use App\Service\LocationServiceAlternateName;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpCoordinate\Coordinate;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class BaseHelperLocationService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class BaseHelperLocationService
{
    protected ?string $error = null;

    protected float $timeStart;

    protected Coordinate $coordinate;

    protected LocationContainer $locationContainer;

    protected LocationServiceAlternateName $locationServiceName;

    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     * @param LocationRepository $locationRepository
     * @param AlternateNameRepository $alternateNameRepository
     * @param TranslatorInterface $translator
     * @param LocationServiceConfig $locationCountryService
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected LocationRepository $locationRepository,
        protected AlternateNameRepository $alternateNameRepository,
        protected TranslatorInterface $translator,
        protected LocationServiceConfig $locationCountryService
    )
    {
        $this->setTimeStart(microtime(true));

        $this->locationServiceName = new LocationServiceAlternateName($alternateNameRepository);
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
        return $this->locationContainer->getDistrict();
    }

    /**
     * @return LocationEntity|null
     */
    public function getBorough(): ?LocationEntity
    {
        return $this->locationContainer->getBorough();
    }

    /**
     * @return LocationEntity|null
     */
    public function getCity(): ?LocationEntity
    {
        return $this->locationContainer->getCity();
    }

    /**
     * @return LocationEntity|null
     */
    public function getState(): ?LocationEntity
    {
        return $this->locationContainer->getState();
    }

    /**
     * @return LocationEntity|null
     */
    public function getCountry(): ?LocationEntity
    {
        return $this->locationContainer->getCountry();
    }
}
