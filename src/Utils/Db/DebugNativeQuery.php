<?php

/*
 * This file is part of the locate-place/php-location-api project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Utils\Db;

use Doctrine\ORM\NativeQuery;
use LogicException;

/**
 * Class DebugQuery
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-04-29)
 * @since 0.1.0 (2024-04-29) First version.
 */
readonly class DebugNativeQuery
{
    /**
     * @param NativeQuery $nativeQuery
     */
    public function __construct(private NativeQuery $nativeQuery)
    {
    }

    /**
     * Returns the raw SQL from the given query builder.
     *
     * @return string
     */
    public function getSqlRaw(): string
    {
        $sql = $this->nativeQuery->getSql();
        $parameters = $this->nativeQuery->getParameters();

        foreach ($parameters as $value) {
            $name = $value->getName();
            $value = $value->getValue();

            $sql = match (true) {
                /* Null values */
                gettype($value) === 'NULL' => str_replace(':'.$name, 'NULL', $sql),

                /* Strings */
                gettype($value) === 'string' => str_replace(':'.$name, sprintf("'%s'", $value), $sql),

                /* Numbers */
                gettype($value) === 'integer',
                gettype($value) === 'double' => str_replace(':'.$name, (string) $value, $sql),

                /* Unknown types */
                default => throw new LogicException(sprintf('Unknown type "%s".', gettype($value))),
            };
        }

        return $sql;
    }
}
