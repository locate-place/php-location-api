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
 * Class DistanceOperator
 * Uses the <-> PostgreSQL distance operator.
 *
 * DistanceOperator ::= "DistanceOperator(field, latitude, longitude[, srid=4326])"
 * DistanceOperator ::= "DistanceOperator" "(" ArithmeticPrimary ","  ArithmeticPrimary ","  ArithmeticPrimary ["," ArithmeticPrimary] ")"
 *
 * @example ORDER BY coordinate_geography <-> 'SRID=4326;POINT(13.72253 51.07706)'::geography ASC
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-29)
 * @since 0.1.0 (2023-07-29) First version.
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class DistanceOperator extends FunctionNode
{
    public Node|string $field;

    public Node|string $latitude;

    public Node|string $longitude;

    public Node|string $srid = '4326';

    /**
     * Parses the given DQL string from QueryBuilder, etc.
     *
     * @param Parser $parser
     * @return void
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);          // DistanceOperator
        $parser->match(Lexer::T_OPEN_PARENTHESIS);    // (
        $this->field = $parser->ArithmeticFactor();         // l.coordinate
        $parser->match(Lexer::T_COMMA);               // ,
        $this->latitude = $parser->ArithmeticFactor();      // 47.473110 (latitude)
        $parser->match(Lexer::T_COMMA);               // ,
        $this->longitude = $parser->ArithmeticFactor();     // 10.813154 (longitude)

        /* Check if srid was given. */
        $lexer = $parser->getLexer();                       // ,4326 or NULL
        $nextType = $lexer->lookahead['type'] ?? $lexer->lookahead->type ?? null;
        if (Lexer::T_COMMA === $nextType) {
            $parser->match(Lexer::T_COMMA);
            $this->srid = $parser->ArithmeticFactor();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);   // )
    }

    /**
     * Returns the SQL query.
     *
     * ---
     * Attention: PostgreSQL uses lon/lat not lat/lon!
     * ---
     *
     * @param SqlWalker $sqlWalker
     * @return string
     * @throws ASTException
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        $field = is_string($this->field) ? $this->field : $this->field->dispatch($sqlWalker);
        $latitude = is_string($this->latitude) ? $this->latitude : $this->latitude->dispatch($sqlWalker);
        $longitude = is_string($this->longitude) ? $this->longitude : $this->longitude->dispatch($sqlWalker);
        $srid = is_string($this->srid) ? $this->srid : $this->srid->dispatch($sqlWalker);

        /* Attention: PostgreSQL uses lon/lat not lat/lon! */
        return sprintf(
            '%s <-> \'SRID=%s;POINT(%s %s)\'',
            $field,
            $srid,
            $longitude,
            $latitude
        );
    }
}
