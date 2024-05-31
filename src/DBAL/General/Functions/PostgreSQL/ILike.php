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

namespace App\DBAL\General\Functions\PostgreSQL;

use Doctrine\ORM\Query\AST\ASTException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Class ILike
 * Uses the ILIKE PostgreSQL compareness operator.
 *
 * @example WHERE l.name ILIKE '%dresden%'
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-22)
 * @since 0.1.0 (2024-01-22) First version.
 */
class ILike extends FunctionNode
{
    public Node|string $field;

    public Node|string $query;

    /**
     * Parses the given DQL string from QueryBuilder, etc.
     *
     * @param Parser $parser
     * @return void
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);        // ILIKE
        $parser->match(TokenType::T_OPEN_PARENTHESIS);  // (
        $this->field = $parser->StringExpression();       // l.name
        $parser->match(TokenType::T_COMMA);             // ,
        $this->query = $parser->StringExpression();       // '%dresden%'
        $parser->match(TokenType::T_CLOSE_PARENTHESIS); // )
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
        $field = is_string($this->field) ? $this->field : $this->field->dispatch($sqlWalker);
        $query = is_string($this->query) ? $this->query : $this->query->dispatch($sqlWalker);

        return sprintf('%s ILIKE %s', $field, $query);
    }
}
