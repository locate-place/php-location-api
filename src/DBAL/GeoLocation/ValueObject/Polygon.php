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
 * Class Polygon
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-16)
 * @since 0.1.0 (2024-03-16) First version.
 */
readonly class Polygon
{
    final public const SRID_WSG84 = 4326;

    /**
     * @param Point[] $points
     * @param int $srid
     */
    public function __construct(private array $points, private int $srid = self::SRID_WSG84)
    {
    }

    /**
     * Returns the points of this polygon.
     *
     * @return Point[]
     */
    public function getPoints(): array
    {
        return $this->points;
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
