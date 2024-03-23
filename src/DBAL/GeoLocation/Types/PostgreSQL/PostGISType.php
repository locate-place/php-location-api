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

use App\DBAL\GeoLocation\Types\PostgreSQL\Base\BasePostGISType;
use App\DBAL\GeoLocation\ValueObject\Linestring;
use App\DBAL\GeoLocation\ValueObject\Point;
use App\DBAL\GeoLocation\ValueObject\Polygon;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Type\TypeInvalidException;
use LogicException;

/**
 * Class PostGISType
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-22)
 * @since 0.1.0 (2024-03-22) First version.
 */
abstract class PostGISType extends BasePostGISType
{
    final public const GEOMETRY = 'geometry';

    final public const GEOGRAPHY = 'geography';

    final public const SRID_ZERO = 0;

    /**
     * Sets whether this type requires a SQL conversion.
     *
     * @return bool
     */
    public function canRequireSQLConversion(): bool
    {
        return true;
    }

    /**
     * Returns the mapped database column names for this type (child classes).
     *
     * @param AbstractPlatform $platform
     * @return array|string[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return [$this->getName()];
    }

    /**
     * Converts the SQL value returned into a PHP value which can be used for internal processing
     * (custom mapping):
     *
     * - DB reading process: SQL value -> PHP value
     * - Opposite of method `self::convertToDatabaseValueSQL`
     *
     * @param string $sqlExpr
     * @param AbstractPlatform $platform
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToPHPValueSQL($sqlExpr, $platform): string
    {
        return sprintf('ST_AsEWKT(%s)', $sqlExpr);
    }

    /**
     * Converts the PHP value to a SQL value, which can be directly used by the DB (custom mapping):
     *
     * - DB writing process: PHP value -> SQL value
     * - Opposite of method `self::convertToPHPValueSQL`
     *
     * @param string $sqlExpr
     * @param AbstractPlatform $platform
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string
    {
        return sprintf('ST_GeomFromEWKT(%s)', $sqlExpr);
    }

    /**
     * Returns the instantiated Point.
     *
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return Point|Linestring|Polygon
     * @throws TypeInvalidException
     * @throws CaseUnsupportedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): Point|Linestring|Polygon
    {
        if (!is_string($value)) {
            throw new TypeInvalidException('string', 'string');
        }

        return match (true) {
            str_contains($value, 'POINT') => $this->convertPointToPHPValue($value),
            str_contains($value, 'LINESTRING') => $this->convertLinestringToPHPValue($value),
            str_contains($value, 'POLYGON') => $this->convertPolygonToPHPValue($value),
            default => throw new LogicException(sprintf('Unsupported geometry/geography type: %s', $value)),
        };
    }

    /**
     * Returns the SQL representation.
     *
     * @param array<int|string, mixed> $column
     * @param AbstractPlatform $platform
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        /** @var array{geometry_type?: string|null, srid?: int|string|null} $column */
        $options = $this->getNormalizedPostGISColumnOptions($column);

        return sprintf(
            '%s(%s, %d)',
            $this->getNameShorten(),
            $options['geometry_type'],
            $options['srid']
        );
    }

    /**
     * Will be set within its child classes.
     *
     * @param array{geometry_type?: string|null, srid?: int|string|null} $options
     * @return array{geometry_type: string, srid: int}
     */
    abstract public function getNormalizedPostGISColumnOptions(array $options = []): array;
}
