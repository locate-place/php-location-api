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

namespace App\DBAL\GeoLocation\ValueObject;

/**
 * Class Point
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-27)
 * @since 0.1.0 (2023-06-27) First version.
 */
readonly class Point
{
    /**
     * @param float $latitude
     * @param float $longitude
     */
    public function __construct(private float $latitude, private float $longitude)
    {
    }

    /**
     * Returns the latitude of the point.
     *
     * - North/South, Ordinate axis, Y axis
     * - range: [-90, 90]
     * - https://en.wikipedia.org/wiki/Latitude
     * - https://de.wikipedia.org/wiki/Geographische_Breite
     *
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * Returns the longitude of the point.
     *
     * - East/West, Abscissa axis, X axis
     * - range: [-180, 180]
     * - https://en.wikipedia.org/wiki/Longitude
     * - https://de.wikipedia.org/wiki/Geographische_L%C3%A4nge
     *
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }
}
