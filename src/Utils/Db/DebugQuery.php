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
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    public function getSqlRaw(): string
    {
        $logger = new DebugStack();
        $this->queryBuilder->getEntityManager()->getConnection()->getConfiguration()->setSQLLogger($logger);

        try {
            $this->queryBuilder->getQuery()->getScalarResult();
        } catch (Throwable) {

        }

        $current = $logger->queries[$logger->currentQuery];

        $sql = $current['sql'];
        $params = $current['params'];

        if (!is_string($sql)) {
            throw new LogicException('The value of sql must be a string.');
        }

        if (!is_array($params)) {
            throw new LogicException('The values of params must be an array.');
        }

//        print $sql.PHP_EOL;
//        print print_r($params, true).PHP_EOL;
//        exit();

        return vsprintf(str_replace('?', '%s', $sql), $params);
    }
}
