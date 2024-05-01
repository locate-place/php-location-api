<?php

/*
 * This file is part of the ixnode/php-api-version-bundle project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Unit\Utils\Query;

use App\Exception\QueryParserException;
use App\Utils\Query\QueryParser;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;
use PHPUnit\Framework\TestCase;

/**
 * Class ParserTest
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-06)
 * @since 0.1.0 (2024-01-06) First version.
 * @link QueryParser
 */
final class ParserTest extends TestCase
{
    /**
     * Test wrapper.
     *
     * @dataProvider dataProviderType
     *
     * @test
     * @testdox $number) Test Parser:getType
     * @param int $number
     * @param string $query
     * @param string $expectedType
     * @param string[]|null $expectedSearch
     * @param string[]|null $expectedFeatureClasses
     * @param string[]|null $expectedFeatureCodes
     * @param int|null $expectedGeonameId
     * @param float|null $expectedLatitude
     * @param float|null $expectedLongitude
     * @param int|null $expectedDistance
     * @param int|null $expectedLimit
     * @param string|null $expectedCountry
     * @param string|null $expectedException
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function wrapperType(
        int $number,
        string $query,
        string $expectedType,
        array|null $expectedSearch,
        array|null $expectedFeatureClasses,
        array|null $expectedFeatureCodes,
        int|null $expectedGeonameId,
        float|null $expectedLatitude,
        float|null $expectedLongitude,
        int|null $expectedDistance,
        int|null $expectedLimit,
        string|null $expectedCountry,
        string|null $expectedException = null
    ): void
    {
        /* Arrange */
        if (!is_null($expectedException)) {
            $this->expectException(QueryParserException::class);
            $this->expectExceptionMessage($expectedException);
        }

        /* Act */
        $parser = new QueryParser($query);

        /* Assert */
        $this->assertIsNumeric($number); // To avoid phpmd warning.
        $this->assertSame($expectedType, $parser->getType());
        $this->assertSame($expectedSearch, $parser->getSearch());
        $this->assertSame($expectedFeatureClasses, $parser->getFeatureClasses());
        $this->assertSame($expectedFeatureCodes, $parser->getFeatureCodes());
        $this->assertSame($expectedGeonameId, $parser->getGeonameId());
        $this->assertSame($expectedLatitude, $parser->getLatitude());
        $this->assertSame($expectedLongitude, $parser->getLongitude());
        $this->assertSame($expectedDistance, $parser->getDistance());
        $this->assertSame($expectedLimit, $parser->getLimit());
        $this->assertSame($expectedCountry, $parser->getCountry());
    }

    /**
     * Data provider.
     *
     * @return array<int, mixed>
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderType(): array
    {
        $number = 0;

        return [
            /* Number,  Search string,                                                     Search type,                                                 Search parts,                       Feature Classes,  Feature Codes,                 Geoname ID,  Latitude,    Longitude,     Distance,  Limit, Country, Exception,                         Description                                           */
            /* ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ */

            /* Typical examples */
            [++$number, '6698681',                                                         QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          6_698_681,   null,        null,          null,      null,  null],                                      /* Der goldene Reiter */
            [++$number, '6698681 distance:50',                                             QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          6_698_681,   null,        null,          50,        null,  null],                                      /* Der goldene Reiter */
            [++$number, '  6698681   distance:50  ',                                       QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          6_698_681,   null,        null,          50,        null,  null],                                      /* Der goldene Reiter */
            [++$number, '6698681 limit:10',                                                QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          6_698_681,   null,        null,          null,      10,    null],                                      /* Der goldene Reiter */
            [++$number, '  6698681   limit:10  ',                                          QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          6_698_681,   null,        null,          null,      10,    null],                                      /* Der goldene Reiter */
            [++$number, '6698681 country:DE',                                              QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          6_698_681,   null,        null,          null,      null,  'DE'],                                      /* Der goldene Reiter */
            [++$number, '  6698681   country:DE  ',                                        QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          6_698_681,   null,        null,          null,      null,  'DE'],                                      /* Der goldene Reiter */
            [++$number, '  6698681  country:DE distance:50 limit:10',                      QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          6_698_681,   null,        null,          50,        10,    'DE'],                                      /* Der goldene Reiter */
            [++$number, '  country:DE distance:50 limit:10  6698681',                      QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          6_698_681,   null,        null,          50,        10,    'DE'],                                      /* Der goldene Reiter */
            [++$number, '  country:DE distance:50 6698681 limit:10  ',                     QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          6_698_681,   null,        null,          50,        10,    'DE'],                                      /* Der goldene Reiter */
            [++$number, '51.05811,13.74133',                                               QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        51.05811,    13.74133,      null,      null,  null],                                      /* Der goldene Reiter */
            [++$number, '51.05811,13.74133 limit:10',                                      QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        51.05811,    13.74133,      null,      10,    null],                                      /* Der goldene Reiter */
            [++$number, '  51.05811,  13.74133   limit:10  ',                              QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        51.05811,    13.74133,      null,      10,    null],                                      /* Der goldene Reiter */
            [++$number, '51,05811,13,74133',                                               QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        51.05811,    13.74133,      null,      null,  null],                                      /* Der goldene Reiter */
            [++$number, '51.05811°,13.74133°',                                             QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        51.05811,    13.74133,      null,      null,  null],                                      /* Der goldene Reiter */
            [++$number, '51°3′29.196″N,13°44′28.788″E',                                    QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        51.05811,    13.74133,      null,      null,  null],                                      /* Der goldene Reiter */
            [++$number, '52°31′12.108″N,13°24′17.604″E',                                   QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,    13.40489,      null,      null,  null],                                      /* Berlin Mitte */
            [++$number, '52°31′12.108″N/13°24′17.604″E',                                   QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,    13.40489,      null,      null,  null],                                      /* Berlin Mitte */
            [++$number, '52°31′12.108″N|13°24′17.604″E',                                   QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,    13.40489,      null,      null,  null],                                      /* Berlin Mitte */
            [++$number, 'AIRP 52°31′12.108″N,13°24′17.604″E',                              QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,  null,                               null,             ['AIRP'],                      null,        52.52003,    13.40489,      null,      null,  null],                                      /* Berlin Mitte */
            [++$number, 'AIRP: 52°31′12.108″N,13°24′17.604″E',                             QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,  null,                               null,             ['AIRP'],                      null,        52.52003,    13.40489,      null,      null,  null],                                      /* Berlin Mitte */
            [++$number, 'AIRP:52°31′12.108″N,13°24′17.604″E',                              QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,  null,                               null,             ['AIRP'],                      null,        52.52003,    13.40489,      null,      null,  null],                                      /* Berlin Mitte */
            [++$number, 'AIRP|AIRT 52°31′12.108″N,13°24′17.604″E',                         QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,  null,                               null,             ['AIRP', 'AIRT'],              null,        52.52003,    13.40489,      null,      null,  null],                                      /* Berlin Mitte */
            [++$number, 'AIRP|AIRT: 52°31′12.108″N,13°24′17.604″E',                        QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,  null,                               null,             ['AIRP', 'AIRT'],              null,        52.52003,    13.40489,      null,      null,  null],                                      /* Berlin Mitte */
            [++$number, 'AIRP|AIRT:52°31′12.108″N,13°24′17.604″E',                         QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,  null,                               null,             ['AIRP', 'AIRT'],              null,        52.52003,    13.40489,      null,      null,  null],                                      /* Berlin Mitte */
            [++$number, 'Berlin Mitte',                                                    QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Berlin', 'Mitte'],                null,             null,                          null,        null,        null,          null,      null,  null],                                      /* Berlin Mitte */
            [++$number, 'Berlin Mitte distance:50',                                        QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Berlin', 'Mitte'],                null,             null,                          null,        null,        null,          50,        null,  null],                                      /* Berlin Mitte */
            [++$number, 'Der goldene Reiter',                                              QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Der', 'goldene', 'Reiter'],       null,             null,                          null,        null,        null,          null,      null,  null],                                      /* Der goldene Reiter */
            [++$number, 'AIRP|AIRT',                                                       QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES,                 null,                               null,             ['AIRP', 'AIRT'],              null,        null,        null,          null,      null,  null],                                      /* Only airports */
            [++$number, 'AIRP|AIRT Dresden',                                               QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Dresden'],                        null,             ['AIRP', 'AIRT'],              null,        null,        null,          null,      null,  null],                                      /* Only airports with search string "Dresden" */
            [++$number, 'AIRP|AIRT:Dresden',                                               QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Dresden'],                        null,             ['AIRP', 'AIRT'],              null,        null,        null,          null,      null,  null],                                      /* Only airports with search string "Dresden" */
            [++$number, 'AIRP|AIRT: Dresden',                                              QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Dresden'],                        null,             ['AIRP', 'AIRT'],              null,        null,        null,          null,      null,  null],                                      /* Only airports with search string "Dresden" */
            [++$number, 'AIRP|AIRT: Berlin Mitte',                                         QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Berlin', 'Mitte'],                null,             ['AIRP', 'AIRT'],              null,        null,        null,          null,      null,  null],                                      /* Only airports with search string "Dresden" */
            [++$number, 'AIRP|AIRT: 6698681',                                              QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_GEONAME_ID,  null,                               null,             ['AIRP', 'AIRT'],              6_698_681,   null,        null,          null,      null,  null],                                      /* Only airports around the given geoname id "197877" */
            [++$number, 'AIRP|AIRT: Test 197877',                                          QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Test', '197877'],                 null,             ['AIRP', 'AIRT'],              null,        null,        null,          null,      null,  null],                                      /* Only airports around the given geoname id "197877" */
            [++$number, 'AIRP|AIRX: Test 197877',                                          QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Test', '197877'],                 null,             ['AIRP', 'AIRT'],              null,        null,        null,          null,      null,  null,  'Unsupported feature code "AIRX"'],  /* Only airports around the given geoname id "197877" */
            [++$number, 'feature-codes:AIRP|AIRT Test 197877',                             QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Test', '197877'],                 null,             ['AIRP', 'AIRT'],              null,        null,        null,          null,      null,  null],                                      /* Only airports around the given geoname id "197877" */
            [++$number, 'feature-classes:S Test 197877',                                   QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Test', '197877'],                 ['S'],            null,                          null,        null,        null,          null,      null,  null],                                      /* Only places around the given geoname id "197877" */
            [++$number, 'Test 197877 feature-classes:S',                                   QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Test', '197877'],                 ['S'],            null,                          null,        null,        null,          null,      null,  null],                                      /* Only places around the given geoname id "197877" */
            [++$number, 'Test 197877 feature-classes:',                                    QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Test', '197877'],                 null,             null,                          null,        null,        null,          null,      null,  null],                                      /* Only places around the given geoname id "197877" */
            [++$number, 'Test 197877 feature-classes:S|T',                                 QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Test', '197877'],                 ['S', 'T'],       null,                          null,        null,        null,          null,      null,  null],                                      /* Only places around the given geoname id "197877" */
            [++$number, 'Test 197877 feature-classes:S,T',                                 QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Test', '197877'],                 ['S', 'T'],       null,                          null,        null,        null,          null,      null,  null],                                      /* Only places around the given geoname id "197877" */
            [++$number, 'Test 197877 feature-classes:s,t',                                 QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Test', '197877'],                 ['S', 'T'],       null,                          null,        null,        null,          null,      null,  null],                                      /* Only places around the given geoname id "197877" */
            [++$number, 'Test 197877 feature-classes:S,T,Z',                               QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,      ['Test', '197877'],                 ['S', 'T'],       null,                          null,        null,        null,          null,      null,  null,  'Unsupported feature class "Z"'],    /* Only places around the given geoname id "197877" */
            [++$number, 'P 51.06115, 13.740701 distance:5000 limit:12',                    QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,  null,                               ['P'],            null,                          null,        51.06115,    13.740701,     5000,      12,    null],                                      /* Only places around the given geoname id "197877" */
            [++$number, '51.06115, 13.740701 feature-classes:P distance:5000 limit:12',    QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,  null,                               ['P'],            null,                          null,        51.06115,    13.740701,     5000,      12,    null],                                      /* Only places around the given geoname id "197877" */
            [++$number, 'Dresden country:CA',                                              QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden'],                        null,             null,                          null,        null,        null,          null,      null,  'CA'],                                      /* Only places around the given geoname id "197877" */



            /* Number,  Search string,                                                     Search type,                                                 Search parts,                       Feature Classes,  Feature Codes,                 Geoname ID,  Latitude,    Longitude,     Distance,  Limit, Country, Exception,                         Description                                           */
            /* ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ */

            /* Geoname search */
            [++$number, '12345678',                                                        QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          12_345_678,  null,        null,          null,      null,  null],                                      /* */
            [++$number, ' 12345678',                                                       QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          12_345_678,  null,        null,          null,      null,  null],                                      /* */
            [++$number, '       12345678',                                                 QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          12_345_678,  null,        null,          null,      null,  null],                                      /* */
            [++$number, '12345678    ',                                                    QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          12_345_678,  null,        null,          null,      null,  null],                                      /* */
            [++$number, '     12345678      ',                                             QueryParser::TYPE_SEARCH_GEONAME_ID,                         null,                               null,             null,                          12_345_678,  null,        null,          null,      null,  null],                                      /* */



            /* Number,  Search string,                                                     Search type,                                                 Search parts,                       Feature Classes,  Feature Codes,                 Geoname ID,  Latitude,    Longitude,     Distance,  Limit, Country, Exception,                         Description                                           */
            /* ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ */

            /* Coordinate search (decimal) */
            [++$number, '52.524889,13.3692797',                                            QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.524889,     13.36928,    null,      null,  null],                                      /* */
            [++$number, '52,524889,13,3692797',                                            QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.524889,     13.36928,    null,      null,  null],                                      /* */
            [++$number, '47.35858,8.530299',                                               QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        47.35858,      8.530299,    null,      null,  null],                                      /* */
            [++$number, '28.137008,-15.438614',                                            QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        28.137008,   -15.438614,    null,      null,  null],                                      /* */
            [++$number, '52.524889, 13.3692797',                                           QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.524889,    13.369280,    null,      null,  null],                                      /* */
            [++$number, '47.35858, 8.530299',                                              QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        47.35858,      8.530299,    null,      null,  null],                                      /* */
            [++$number, '28.137008, -15.438614',                                           QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        28.137008,   -15.438614,    null,      null,  null],                                      /* */
            [++$number, '     52.524889, 13.3692797',                                      QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.524889,    13.369280,    null,      null,  null],                                      /* */
            [++$number, '47.35858, 8.530299     ',                                         QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        47.35858,      8.530299,    null,      null,  null],                                      /* */
            [++$number, '     28.137008,    -15.438614   ',                                QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        28.137008,   -15.438614,    null,      null,  null],                                      /* */
            [++$number, '     -12.073136,   -77.167578   ',                                QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        -12.073136,  -77.167578,    null,      null,  null],                                      /* */
            [++$number, '52.524889 13.3692797',                                            QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.524889,    13.369280,    null,      null,  null],                                      /* */
            [++$number, '47.35858 8.530299',                                               QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        47.35858,      8.530299,    null,      null,  null],                                      /* */
            [++$number, '  28.137008    -15.438614 ',                                      QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        28.137008,   -15.438614,    null,      null,  null],                                      /* */
            [++$number, '52.524889|13.3692797',                                            QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.524889,    13.369280,    null,      null,  null],                                      /* */
            [++$number, '47.35858|8.530299',                                               QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        47.35858,      8.530299,    null,      null,  null],                                      /* */
            [++$number, '  28.137008  |  -15.438614 ',                                     QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        28.137008,   -15.438614,    null,      null,  null],                                      /* */
            [++$number, '  28.137008  /  -15.438614 ',                                     QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        28.137008,   -15.438614,    null,      null,  null],                                      /* */

            /* Coordinate search (dms) */
            [++$number, '52°31′12.108″N,13°24′17.604″E',                                   QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,     13.404890,    null,      null,  null],                                      /* */
            [++$number, '   52°31′12.108″N,13°24′17.604″E    ',                            QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,     13.404890,    null,      null,  null],                                      /* */
            [++$number, '   52°31′12.108″N   ,    13°24′17.604″E    ',                     QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,     13.404890,    null,      null,  null],                                      /* */
            [++$number, '   52°31′12.108″N       13°24′17.604″E    ',                      QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,     13.404890,    null,      null,  null],                                      /* */
            [++$number, '   52°31′12.108″N   |    13°24′17.604″E    ',                     QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,     13.404890,    null,      null,  null],                                      /* */
            [++$number, '   52°31′12.108″N   /    13°24′17.604″E    ',                     QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,     13.404890,    null,      null,  null],                                      /* */

            /* Coordinate search (mixed) */
            [++$number, '   28.137008       13°24′17.604″E    ',                           QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        28.137008,    13.404890,    null,      null,  null],                                      /* */
            [++$number, '   52°31′12.108″N       -15.438614    ',                          QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,    -15.438614,    null,      null,  null],                                      /* */
            [++$number, '   52°31′12.108″N   ,    -15.438614    ',                         QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,    -15.438614,    null,      null,  null],                                      /* */
            [++$number, '   52°31′12.108″N   |    -15.438614    ',                         QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,    -15.438614,    null,      null,  null],                                      /* */
            [++$number, '   52°31′12.108″N   /    -15.438614    ',                         QueryParser::TYPE_SEARCH_COORDINATE,                         null,                               null,             null,                          null,        52.52003,    -15.438614,    null,      null,  null],                                      /* */



            /* Number,  Search string,                                                     Search type,                                                 Search parts,                       Feature Classes,  Feature Codes,                 Geoname ID,  Latitude,    Longitude,     Distance,  Limit, Country, Exception,                         Description                                           */
            /* ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ */

            /* List search */
            [++$number, 'Dresden',                                                         QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden'],                        null,             null,                          null,        null,       null,           null,      null,  null],                                      /* Simple search for Dresden */
            [++$number, 'Dresden:',                                                        QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden'],                        null,             null,                          null,        null,       null,           null,      null,  null],                                      /* Simple search for Dresden */
            [++$number, '(Dresden)',                                                       QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden'],                        null,             null,                          null,        null,       null,           null,      null,  null],                                      /* Simple search for Dresden */
            [++$number, 'Lommatzsch',                                                      QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Lommatzsch'],                     null,             null,                          null,        null,       null,           null,      null,  null],                                      /* Simple search for Lommatzsch */
            [++$number, 'Dresden Flughafen',                                               QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden', 'Flughafen'],           null,             null,                          null,        null,       null,           null,      null,  null],                                      /* Simple search for Dresden Flughafen */
            [++$number, 'Dresden Flughafen country:DE distance:50 limit:10',               QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden', 'Flughafen'],           null,             null,                          null,        null,       null,           50,        10,    'DE'],                                      /* */
            [++$number, 'Dresden country:DE distance:50 limit:10 Flughafen',               QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden', 'Flughafen'],           null,             null,                          null,        null,       null,           50,        10,    'DE'],                                      /* */
            [++$number, 'Dresden country:DE distance:50 Flughafen limit:10',               QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden', 'Flughafen'],           null,             null,                          null,        null,       null,           50,        10,    'DE'],                                      /* */
            [++$number, '"Dresden Flughafen" Klotzsche country:DE distance:50 limit:10',   QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden Flughafen', 'Klotzsche'], null,             null,                          null,        null,       null,           50,        10,    'DE'],                                      /* */
            [++$number, '\'Dresden Flughafen\' Klotzsche country:DE distance:50 limit:10', QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden Flughafen', 'Klotzsche'], null,             null,                          null,        null,       null,           50,        10,    'DE'],                                      /* */


            /* Number,  Search string,                                                     Search type,                                                 Search parts,                       Feature Classes,  Feature Codes,                 Geoname ID,  Latitude,    Longitude,     Distance,  Limit, Country, Exception,                         Description                                           */
            /* ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ */

            /* Unfinished search */
            [++$number, 'Dresden country:DE',                                              QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden'],                        null,             null,                          null,        null,       null,           null,      null,  'DE'],                                      /* Simple search for Dresden */
            [++$number, 'Dresden country:',                                                QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden'],                        null,             null,                          null,        null,       null,           null,      null,  null],                                      /* Simple search for Dresden */
            [++$number, 'Dresden countr',                                                  QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden', 'countr'],              null,             null,                          null,        null,       null,           null,      null,  null],                                      /* Simple search for Dresden */
            [++$number, 'Dresden country:D',                                               QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden'],                        null,             null,                          null,        null,       null,           null,      null,  null],                                      /* Simple search for Dresden */
            [++$number, 'Dresden co',                                                      QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden', 'co'],                  null,             null,                          null,        null,       null,           null,      null,  null],                                      /* Simple search for Dresden */
            [++$number, 'Dresden country:US',                                              QueryParser::TYPE_SEARCH_LIST_GENERAL,                       ['Dresden'],                        null,             null,                          null,        null,       null,           null,      null,  'US'],                                      /* Simple search for Dresden */
        ];
    }


    /**
     * Test wrapper.
     *
     * @dataProvider dataProviderData
     *
     * @test
     * @testdox $number) Test Parser:getData
     * @param int $number
     * @param string|int $query
     * @param array<string, mixed> $expectedData
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function wrapperData(int $number, string|int $query, array $expectedData): void
    {
        /* Arrange */

        /* Act */
        $parser = new QueryParser($query);

        /* Assert */
        $this->assertIsNumeric($number); // To avoid phpmd warning.
        $this->assertSame($expectedData, $parser->getData());
    }

    /**
     * Data provider.
     *
     * @return array<int, mixed>
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderData(): array
    {
        $number = 0;

        /* Typical examples */
        return [
            [++$number, $geonameId = 197877, QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_GEONAME_ID,
                geonameId: $geonameId,
            )], /* Der goldene Reiter */

            [++$number, '51.05811,13.74133', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_COORDINATE,
                latitude: 51.05811,
                longitude: 13.74133,
            )], /* Der goldene Reiter */

            [++$number, '51.05811°,13.74133°', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_COORDINATE,
                latitude: 51.05811,
                longitude: 13.74133,
            )], /* Der goldene Reiter */

            [++$number, '51°3′29.196″N,13°44′28.788″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_COORDINATE,
                latitude: 51.05811,
                longitude: 13.74133,
            )], /* Der goldene Reiter */

            [++$number, '52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_COORDINATE,
                latitude: 52.52003,
                longitude: 13.40489,
            )], /* Berlin-Mitte */

            [++$number, '52°31′12.108″N/13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_COORDINATE,
                latitude: 52.52003,
                longitude: 13.40489,
            )], /* Berlin-Mitte */

            [++$number, '52°31′12.108″N|13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_COORDINATE,
                latitude: 52.52003,
                longitude: 13.40489,
            )], /* Berlin-Mitte */

            [++$number, 'AIRP 52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,
                latitude: 52.52003,
                longitude: 13.40489,
                featureCodes: ['AIRP'],
            )], /* Berlin-Mitte */

            [++$number, 'AIRP: 52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,
                latitude: 52.52003,
                longitude: 13.40489,
                featureCodes: ['AIRP'],
            )], /* Berlin-Mitte */

            [++$number, 'AIRP:52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,
                latitude: 52.52003,
                longitude: 13.40489,
                featureCodes: ['AIRP'],
            )], /* Berlin-Mitte */

            [++$number, 'AIRP|AIRT 52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,
                latitude: 52.52003,
                longitude: 13.40489,
                featureCodes: ['AIRP', 'AIRT'],
            )], /* Berlin-Mitte */

            [++$number, 'AIRP|AIRT: 52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,
                latitude: 52.52003,
                longitude: 13.40489,
                featureCodes: ['AIRP', 'AIRT'],
            )], /* Berlin-Mitte */

            [++$number, 'AIRP|AIRT:52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,
                latitude: 52.52003,
                longitude: 13.40489,
                featureCodes: ['AIRP', 'AIRT'],
            )], /* Berlin-Mitte */

            [++$number, 'S:52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,
                latitude: 52.52003,
                longitude: 13.40489,
                featureClasses: ['S'],
            )], /* Berlin-Mitte */

            [++$number, 'Berlin Mitte', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_GENERAL,
                search: 'Berlin Mitte',
            )],

        ];
    }
}
