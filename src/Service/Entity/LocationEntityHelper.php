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

namespace App\Service\Entity;

use App\Constants\DB\FeatureCode;
use App\Constants\Language\LanguageCode;
use App\Entity\Location;
use App\Service\LocationContainer;
use LogicException;

/**
 * Class LocationEntityHelper
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */
class LocationEntityHelper
{
    private const LENGTH_IATA_CODE = 3;

    private const LENGTH_ICAO_CODE = 4;

    protected Location $location;

    /**
     * @param LocationContainer $locationContainer
     */
    public function __construct(protected LocationContainer $locationContainer)
    {
    }

    /**
     * Sets the current location entity.
     *
     * @param Location $location
     * @return self
     */
    public function setLocation(Location $location): self
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Returns the airport codes (IATA and ICAO) for the given location.
     *
     * @return array<string, string>|null
     */
    public function getAirportCodes(): array|null
    {
        if (!isset($this->location)) {
            throw new LogicException('Location entity is not set. Use LocationEntityHelper::setLocation() before.');
        }

        $featureCode = $this->location->getFeatureCode()?->getCode();

        if ($featureCode !== FeatureCode::AIRP) {
            return null;
        }

        $data = [];

        $iata = $this->locationContainer->getAlternateName($this->location, LanguageCode::IATA);
        if (!is_null($iata) && strlen($iata) === self::LENGTH_IATA_CODE) {
            $data[LanguageCode::IATA] = $iata;
        }

        $icao = $this->locationContainer->getAlternateName($this->location, LanguageCode::ICAO);
        if (!is_null($icao) && strlen($icao) === self::LENGTH_ICAO_CODE) {
            $data[LanguageCode::ICAO] = $icao;
        }

        return $data;
    }

    /**
     * Returns if airport codes (IATA and ICAO) are available for the given location.
     *
     * @return bool
     */
    public function hasAirportCodes(): bool
    {
        $airportCodes = $this->getAirportCodes();

        if (is_null($airportCodes)) {
            return false;
        }

        return count($airportCodes) > 0;
    }

    /**
     * Returns if airport code IATA is available for the given location.
     *
     * @return bool
     */
    public function hasAirportCodeIata(): bool
    {
        $airportCodes = $this->getAirportCodes();

        if (is_null($airportCodes)) {
            return false;
        }

        return in_array(LanguageCode::IATA, array_keys($airportCodes), true);
    }

    /**
     * Returns if airport code ICAO is available for the given location.
     *
     * @return bool
     */
    public function hasAirportCodeIcao(): bool
    {
        $airportCodes = $this->getAirportCodes();

        if (is_null($airportCodes)) {
            return false;
        }

        return in_array(LanguageCode::ICAO, array_keys($airportCodes), true);
    }

    /**
     * Helper function toString.
     *
     * @param array<int, string>|string|null $value
     * @return string
     */
    public function toString(array|string|null $value = null): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_null($value)) {
            return '';
        }

        return implode(', ', $value);
    }
}
