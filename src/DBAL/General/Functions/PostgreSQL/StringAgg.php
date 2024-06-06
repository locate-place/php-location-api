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
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Class StringAgg
 * Uses the string_agg PostgreSQL operator.
 *
 * @example string_agg(DISTINCT a.alternateName, ',' ORDER BY a.alternateName) AS alternateNames
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-23)
 * @since 0.1.0 (2024-03-23) First version.
 */
class StringAgg extends FunctionNode
{
    private PathExpression $expression;

    private Node $delimiter;

    private bool $isDistinct = false;

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
        $lexer = $parser->getLexer();

        $parser->match(TokenType::T_IDENTIFIER);                                                 // string_agg
        $parser->match(TokenType::T_OPEN_PARENTHESIS);                                           // (

        if ($lexer->isNextToken(TokenType::T_DISTINCT)) {                                         // [DISTINCT]
            $parser->match(TokenType::T_DISTINCT);
            $this->isDistinct = true;
        }

        $this->expression = $parser->PathExpression(PathExpression::TYPE_STATE_FIELD); // a.alternateName
        $parser->match(TokenType::T_COMMA);                                                      // ,
        $this->delimiter = $parser->StringPrimary();                                               // ', '

        if ($lexer->isNextToken(TokenType::T_ORDER)) {                                            // [ORDER BY a.alternateName]
            $this->orderBy = $parser->OrderByClause();
        }

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
        return sprintf('string_agg(%s%s, %s%s)',
            ($this->isDistinct ? 'DISTINCT ' : ''),
            $sqlWalker->walkPathExpression($this->expression),
            $sqlWalker->walkStringPrimary($this->delimiter),
            ($this->orderBy ? $sqlWalker->walkOrderByClause($this->orderBy) : '')
        );
    }
}
