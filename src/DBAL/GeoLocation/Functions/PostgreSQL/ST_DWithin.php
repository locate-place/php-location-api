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

namespace App\DBAL\GeoLocation\Functions\PostgreSQL;

use Doctrine\ORM\Query\AST\ASTException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Class ST_DWithinFunction
 *
 * ST_DWithinFunction ::= "ST_DWithin" "(" ArithmeticPrimary "," ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * @example WHERE ST_DWithin(
 *     ST_MakePoint(coordinate(0), coordinate(1))::geography,
 *     ST_MakePoint(47.473110, 10.813154)::geography,
 *     10000
 * )
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-02)
 * @since 0.1.0 (2023-07-02) First version.
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class ST_DWithin extends FunctionNode
{
    /** @var array<int, object> */
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
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->expressions[] = $parser->ArithmeticFactor();

        $parser->match(Lexer::T_COMMA);

        $this->expressions[] = $parser->ArithmeticFactor();

        $parser->match(Lexer::T_COMMA);

        $this->expressions[] = $parser->ArithmeticFactor();

        $lexer = $parser->getLexer();

        $nextType = $lexer->lookahead['type'] ?? $lexer->lookahead->type ?? null;

        if (Lexer::T_COMMA === $nextType) {
            $parser->match(Lexer::T_COMMA);
            $this->expressions[] = $parser->ArithmeticFactor();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
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

        return sprintf('ST_DWithin(%s)', implode(', ', $arguments));
    }
}
