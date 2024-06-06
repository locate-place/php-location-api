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

namespace App\DBAL\GeoLocation\Functions\PostgreSQL;

use Doctrine\ORM\Query\AST\ASTException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Class ST_AsText
 *
 * ST_AsText ::= "ST_AsText" "(" ArithmeticPrimary ")"
 *
 * @example WHERE ST_AsText(
 *   ST_ClosestPoint(
 *     coordinates::geometry,
 *     ST_MakePoint(13.741670, 51.058330)::geography::geometry
 *   )
 * )
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-21)
 * @since 0.1.0 (2024-03-21) First version.
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class ST_AsText extends FunctionNode
{
    /** @var array<int, object|string> */
    protected array $expressions = [];

    /**
     * Parses the given DQL string from QueryBuilder, etc.
     *
     * @param Parser $parser
     * @return void
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->expressions[] = $parser->ArithmeticFactor();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    /**
     * Returns the SQL query.
     *
     * @param SqlWalker $sqlWalker
     * @return string
     * @throws ASTException
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        $arguments = [];

        /** @var Node $expression */
        foreach ($this->expressions as $expression) {
            $arguments[] = $expression->dispatch($sqlWalker);
        }

        return sprintf('ST_AsText(%s)', implode(', ', $arguments));
    }
}
