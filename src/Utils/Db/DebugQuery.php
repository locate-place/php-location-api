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

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Throwable;

/**
 * Class DebugQuery
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-24)
 * @since 0.1.0 (2024-03-24) First version.
 */
readonly class DebugQuery
{
    private DebugStack $logger;

    /**
     * @param QueryBuilder $queryBuilder
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    public function __construct(private QueryBuilder $queryBuilder)
    {
        $this->logger = new DebugStack();

        $this->queryBuilder->getEntityManager()->getConnection()->getConfiguration()->setSQLLogger($this->logger);

        try {
            $this->queryBuilder->getQuery()->getScalarResult();
        } catch (Throwable) {

        }
    }

    /**
     * Returns the raw SQL from the given query builder.
     *
     * @return string
     */
    public function getSqlRaw(): string
    {
        $sql = $this->getSql();
        $params = $this->getParameter();

        return vsprintf(str_replace('?', '%s', $sql), $params).PHP_EOL;
    }

    /**
     * Returns the raw SQL from the given query builder.
     *
     * @return string
     */
    private function getSql(): string
    {
        $current = $this->logger->queries[$this->logger->currentQuery];

        $sql = $current['sql'];

        if (!is_string($sql)) {
            throw new LogicException('The value of sql must be a string.');
        }

        return $this->formatSqlQuery($sql);
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
     * Returns the parameter from the given query builder.
     *
     * @return array<int, string|int|float>
     */
    private function getParameter(): array
    {
        $current = $this->logger->queries[$this->logger->currentQuery];

        $params = $current['params'];

        if (!is_array($params)) {
            throw new LogicException('The values of params must be an array.');
        }

        return $this->convertParameter($params);
    }

    /**
     * Converts given parameter to PostgreSQL format.
     *
     * @param array<int, mixed> $parameters
     * @return array<int, string|int|float>
     */
    private function convertParameter(array $parameters): array
    {
        $parameterConverted = [];

        foreach ($parameters as $parameter) {
            $parameter = match (true) {
                is_string($parameter) => sprintf("'%s'", $parameter),

                is_int($parameter),
                is_float($parameter) => $parameter,

                is_bool($parameter) => $parameter ? 'TRUE' : 'FALSE',
                is_null($parameter) => 'NULL',

                is_array($parameter) => implode(', ', $this->convertParameter($parameter)),

                default => throw new LogicException(sprintf('Unknown type "%s".', gettype($parameter))),
            };

            $parameterConverted[] = $parameter;
        }

        return $parameterConverted;
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
