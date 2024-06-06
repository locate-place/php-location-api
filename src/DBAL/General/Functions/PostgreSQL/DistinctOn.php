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

namespace App\DBAL\General\Functions\PostgreSQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Class StringAgg
 * Uses the DISTINCT ON PostgreSQL functionality.
 *
 * @example DISTINCT_ON(r.id) AS r_id
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-23)
 * @since 0.1.0 (2024-03-23) First version.
 */
class DistinctOn extends FunctionNode
{
    private PathExpression $expression;

    public OrderByClause|null $orderBy = null;

    /**
     * Parses the given DQL string from QueryBuilder, etc.
     *
     * @param Parser $parser
     * @return void
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);                                                 // DISTINCT_ON
        $parser->match(TokenType::T_OPEN_PARENTHESIS);                                           // (
        $this->expression = $parser->PathExpression(PathExpression::TYPE_STATE_FIELD); // r.id
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);                                          // )
    }

    /**
     * Returns the SQL query.
     *
     * @param SqlWalker $sqlWalker
     * @return string
     * @throws QueryException
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        $expression = $sqlWalker->walkPathExpression($this->expression);

        return sprintf('DISTINCT ON (%s) %s',
            $expression,
            $expression,
        );
    }
}
