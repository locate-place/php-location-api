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

namespace App\Utils\Db;

use App\Entity\Country;
use Doctrine\ORM\QueryBuilder;
use LogicException;

/**
 * Class DebugQuery
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-24)
 * @since 0.1.0 (2024-03-24) First version.
 */
readonly class DebugQuery
{
    /**
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(private QueryBuilder $queryBuilder)
    {
    }

    /**
     * Returns the raw SQL from the given query builder.
     *
     * @return string
     */
    public function getSqlRaw(): string
    {
        $sql = $this->queryBuilder->getQuery()->getSQL();
        $parameters = $this->queryBuilder->getParameters();

        if (!is_string($sql)) {
            throw new LogicException('SQL must be a string.');
        }

        foreach ($parameters as $parameter) {
            $parameter = $parameter->getValue();

            $sql = match (true) {
                /* Null values */
                gettype($parameter) === 'NULL' => $this->strReplaceFirst('?', 'NULL', $sql),

                /* Strings */
                gettype($parameter) === 'string' => $this->strReplaceFirst('?', sprintf("'%s'", $parameter), $sql),

                /* Numbers */
                gettype($parameter) === 'integer',
                gettype($parameter) === 'double' => $this->strReplaceFirst('?', (string) $parameter, $sql),

                /* Array */
                gettype($parameter) === 'array' => $this->strReplaceFirst('?', $this->getString($parameter), $sql),

                /* Object */
                gettype($parameter) === 'object' && $parameter instanceof Country => $this->strReplaceFirst('?', (string) $parameter->getId(), $sql),

                /* Unknown types */
                default => throw new LogicException(sprintf('Unknown type "%s".', gettype($parameter))),
            };
        }

        return $this->formatSqlQuery($sql);
    }

    /**
     * @param array<int, null|string|int|float> $values
     * @return string
     */
    public function getString(array $values): string
    {
        $string = [];

        foreach ($values as $value) {
            $string[] = match (true) {
                /* Null values */
                gettype($value) === 'NULL' => 'NULL',

                /* Strings */
                gettype($value) === 'string' => sprintf("'%s'", $value),

                /* Numbers */
                gettype($value) === 'integer',
                gettype($value) === 'double' => (string) $value,
            };
        }

        return implode(', ', $string);
    }

    /**
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public function strReplaceFirst(string $search, string $replace, string $subject): string
    {
        $pos = strpos($subject, $search);

        if ($pos !== false) {
            return substr_replace($subject, (string) $replace, $pos, strlen($search));
        }

        return $subject;
    }

    /**
     * Formats the given SQL query.
     *
     * @param string $sql
     * @return string
     */
    private function formatSqlQuery(string $sql): string
    {
        /* Replace commas with comma + newline for SELECT fields */
        $sql = $this->pregReplace('~,\s*~', ",\n    ", $sql);

        /* Add newlines before SELECT, FROM, JOINs, WHERE, GROUP BY, and LIMIT */
        $sql = $this->pregReplace('~SELECT\s~', "\nSELECT\n    ", $sql);
        $sql = $this->pregReplace('~\sFROM\s~', "\nFROM\n    ", $sql);
        $sql = $this->pregReplace('~\sINNER JOIN\s~', "\nINNER JOIN\n    ", $sql);
        $sql = $this->pregReplace('~\sLEFT JOIN\s~', "\nLEFT JOIN\n    ", $sql);
        $sql = $this->pregReplace('~\sWHERE\s~', "\nWHERE\n    ", $sql);
        $sql = $this->pregReplace('~\sGROUP BY\s~', "\nGROUP BY\n    ", $sql);
        $sql = $this->pregReplace('~\sORDER\s~', "\nORDER\n    ", $sql);
        $sql = $this->pregReplace('~\sLIMIT\s~', "\nLIMIT\n    ", $sql);

        /* Handle line breaks for AND within WHERE clauses */
        return $this->pregReplace('/\sAND\s/', "\n    AND ", $sql);
    }

    /**
     * Use the preg_replace function and check for string.
     *
     * @param string $pattern
     * @param string $replacement
     * @param string $subject
     * @return string
     */
    private function pregReplace(string $pattern, string $replacement, string $subject): string
    {
        $value = preg_replace($pattern, $replacement, $subject);

        if (!is_string($value)) {
            throw new LogicException('Unable to replace the given pattern.');
        }

        return $value;
    }
}
