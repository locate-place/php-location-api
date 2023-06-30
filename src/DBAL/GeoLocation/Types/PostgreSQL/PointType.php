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
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Ixnode\PhpException\Type\TypeInvalidException;

/**
 * Class PointType
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-27)
 * @since 0.1.0 (2023-06-27) First version.
 */
class PointType extends Type
{
    private const POINT = 'point';

    /**
     * Returns the name of this type.
     *
     * @return string
     */
    public function getName(): string
    {
        return self::POINT;
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
        return 'POINT';
    }

    /**
     * Returns the instantiated Point.
     *
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return Point
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): Point
    {
        if (!is_string($value)) {
            throw new TypeInvalidException('string', 'string');
        }

        $result = sscanf($value, '(%f, %f)');

        if (is_null($result)) {
            throw new TypeInvalidException('array', 'null');
        }

        [$latitude, $longitude] = $result;

        if (is_null($latitude)) {
            throw new TypeInvalidException('float', 'null');
        }

        if (is_null($longitude)) {
            throw new TypeInvalidException('float', 'null');
        }

        return new Point($latitude, $longitude);
    }

    /**
     * Returns the database value.
     *
     * @param Point $value
     * @param AbstractPlatform $platform
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        return sprintf('%f,%f', $value->getLatitude(), $value->getLongitude());
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
        return sprintf('PointFromText(%s)', $sqlExpr);
    }
}
