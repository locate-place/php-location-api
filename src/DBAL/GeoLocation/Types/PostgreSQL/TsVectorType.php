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

use App\DBAL\GeoLocation\ValueObject\TsVector;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use LogicException;

/**
 * Class TsVectorType
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-04-07)
 * @since 0.1.0 (2024-04-07) First version.
 */
class TsVectorType extends Type
{
    final public const TS_VECTOR = 'tsvector';

    /**
     * Returns the name of this type.
     *
     * @return string
     */
    public function getName(): string
    {
        return self::TS_VECTOR;
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
        return sprintf('%s', $sqlExpr);
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
        return sprintf('to_tsvector(%s)', $sqlExpr);
    }

    /**
     * Returns the instantiated Point.
     *
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return TsVector|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): TsVector|null
    {
        if (is_null($value)) {
            return null;
        }

        if (!is_string($value)) {
            throw new LogicException(sprintf('Expected a string, got "%s"', gettype($value)));
        }

        return new TsVector($value);
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
        return self::TS_VECTOR;
    }
}
