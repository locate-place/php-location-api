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
 * Class ST_MakePointPointFunction
 *
 * ST_MakePointPointFunction ::= "ST_MakePoint" "(" StringPrimary ArithmeticPrimary "," StringPrimary ArithmeticPrimary ")"
 *
 * @example ST_MakePointPoint(coordinate(0), coordinate(1))::geography,
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-02)
 * @since 0.1.0 (2023-07-02) First version.
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class ST_MakePointPoint extends FunctionNode
{
    public Node $fieldX;
    public Node $fieldY;

    public Node|string $pointX;
    public Node|string $pointY;

    /**
     * Parses the given DQL string from QueryBuilder, etc.
     *
     * @param Parser $parser
     * @return void
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER); // ST_MakePointPoint
        $parser->match(Lexer::T_OPEN_PARENTHESIS); // (
        $this->fieldX = $parser->StringPrimary(); // coordinate
        $parser->match(Lexer::T_OPEN_PARENTHESIS); // (
        $this->pointX = $parser->ArithmeticPrimary(); // 0
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); // )
        $parser->match(Lexer::T_COMMA); // ,
        $this->fieldY = $parser->StringPrimary(); // coordinate
        $parser->match(Lexer::T_OPEN_PARENTHESIS); // (
        $this->pointY = $parser->ArithmeticPrimary(); // 1
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); // )
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); // )
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
        $fieldX = $this->fieldX->dispatch($sqlWalker);
        $pointX = is_string($this->pointX) ? $this->pointX : $this->pointX->dispatch($sqlWalker);
        $fieldY = $this->fieldY->dispatch($sqlWalker);
        $pointY = is_string($this->pointY) ? $this->pointY : $this->pointY->dispatch($sqlWalker);

        return sprintf(
            'ST_MakePoint(%s[%d], %s[%d])::geography',
            $fieldX,
            $pointX,
            $fieldY,
            $pointY
        );
    }
}
