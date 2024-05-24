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

use Stringable;

/**
 * Class Point
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.2 (2024-05-24)
 * @since 0.1.2 (2024-05-24) Add __toString function.
 * @since 0.1.1 (2023-07-31) Add srid.
 * @since 0.1.0 (2023-06-27) First version.
 */
readonly class Point implements Stringable
{
    final public const SRID_WSG84 = 4326;

    /**
     * @param float $latitude
     * @param float $longitude
     * @param int $srid
     */
    public function __construct(private float $latitude, private float $longitude, private int $srid = self::SRID_WSG84)
    {
    }

    /**
     * toString method.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getString();
    }

    /**
     * Returns the point string of this point.
     *
     * @return string
     */
    public function getString(): string
    {
        /* Attention: PostgreSQL uses lon/lat not lat/lon! */
        return sprintf(
            'SRID=%s;POINT(%s %s)',
            $this->srid,
            $this->longitude,
            $this->latitude
        );
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

    /**
     * Returns the srid value of this point (WSG84).
     *
     * @return int
     */
    public function getSrid(): int
    {
        return $this->srid;
    }
}
