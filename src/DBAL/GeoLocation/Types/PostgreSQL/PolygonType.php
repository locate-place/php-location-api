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

namespace App\DBAL\GeoLocation\Types\PostgreSQL;

use App\DBAL\GeoLocation\ValueObject\Point;
use App\DBAL\GeoLocation\ValueObject\Polygon;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Ixnode\PhpException\Type\TypeInvalidException;

/**
 * Class PolygonType
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-16)
 * @since 0.1.0 (2024-03-16) First version.
 */
class PolygonType extends Type
{
    final public const POLYGON = 'polygon';

    /**
     * Returns the name of this type.
     *
     * @return string
     */
    public function getName(): string
    {
        return self::POLYGON;
    }

    /**
     * Returns the SQL declaration.
     *
     * @param array<int|string, mixed> $column
     * @param AbstractPlatform $platform
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'POLYGON';
    }

    /**
     * Returns the instantiated Point.
     *
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return Polygon
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): Polygon
    {
        if (!is_string($value)) {
            throw new TypeInvalidException('string', 'string');
        }

        $coordinatePairs = explode(", ", trim($value, "()"));

        $coordinates = [];
        foreach ($coordinatePairs as $pair) {
            if (!is_string($pair)) {
                throw new TypeInvalidException('string', 'string');
            }

            $result = sscanf($pair, "%f %f");

            if (is_null($result)) {
                throw new TypeInvalidException('array', 'null');
            }

            [$longitude, $latitude] = $result;

            if (is_null($latitude)) {
                throw new TypeInvalidException('float', 'null');
            }

            if (is_null($longitude)) {
                throw new TypeInvalidException('float', 'null');
            }

            $coordinates[] = new Point($latitude, $longitude);
        }

        return new Polygon($coordinates);
    }

    /**
     * Returns the database value.
     *
     * @param Polygon $value
     * @param AbstractPlatform $platform
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        $points = array_map(fn(Point $point) => sprintf('%f %f', $point->getLatitude(), $point->getLongitude()), $value->getPoints());

        return implode(', ', $points);
    }

    /**
     * Returns the database SQL value.
     *
     * @param string $sqlExpr
     * @param AbstractPlatform $platform
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string
    {
        return sprintf('PolygonFromText(%s)', $sqlExpr);
    }
}
