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
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Class ST_MakePoint
 *
 * ST_MakePoint ::= "ST_MakePoint" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * @example ST_MakePoint(47.473110, 10.813154)::geography
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-02)
 * @since 0.1.0 (2023-07-02) First version.
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class ST_MakePoint extends FunctionNode
{
    public Node|string $latitude;

    public Node|string $longitude;

    /**
     * Parses the given DQL string from QueryBuilder, etc.
     *
     * @param Parser $parser
     * @return void
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER); // ST_MakePoint
        $parser->match(TokenType::T_OPEN_PARENTHESIS); // (
        $this->latitude = $parser->ArithmeticFactor(); // 47.473110 | -1.473110
        $parser->match(TokenType::T_COMMA); // ,
        $this->longitude = $parser->ArithmeticFactor(); // 10.813154 | -1.473110
        $parser->match(TokenType::T_CLOSE_PARENTHESIS); // )
    }

    /**
     * Parses the given DQL string from QueryBuilder, etc.
     *
     * @param SqlWalker $sqlWalker
     * @return string
     * @throws ASTException
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        $latitude = is_string($this->latitude) ? $this->latitude : $this->latitude->dispatch($sqlWalker);
        $longitude = is_string($this->longitude) ? $this->longitude : $this->longitude->dispatch($sqlWalker);

        return sprintf('ST_MakePoint(%s, %s)::geography', $latitude, $longitude);
    }
}
