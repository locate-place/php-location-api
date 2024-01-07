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
     */
    public function wrapperType(int $number, string $query, string $expectedType): void
    {
        /* Arrange */

        /* Act */
        $parser = new QueryParser($query);

        /* Assert */
        $this->assertIsNumeric($number); // To avoid phpmd warning.
        $this->assertSame($expectedType, $parser->getType());
    }

    /**
     * Data provider.
     *
     * @return array<int, mixed>
     */
    public function dataProviderType(): array
    {
        $number = 0;

        return [
            /* Typical examples */
            [++$number, '197877', QueryParser::TYPE_SEARCH_GEONAME_ID], /* Der goldene Reiter */
            [++$number, '51.05811,13.74133', QueryParser::TYPE_SEARCH_COORDINATE], /* Der goldene Reiter */
            [++$number, '51.05811°,13.74133°', QueryParser::TYPE_SEARCH_COORDINATE], /* Der goldene Reiter */
            [++$number, '51°3′29.196″N,13°44′28.788″E', QueryParser::TYPE_SEARCH_COORDINATE], /* Der goldene Reiter */
            [++$number, '52°31′12.108″N,13°24′17.604″E', QueryParser::TYPE_SEARCH_COORDINATE], /* Berlin Mitte */
            [++$number, '52°31′12.108″N/13°24′17.604″E', QueryParser::TYPE_SEARCH_COORDINATE], /* Berlin Mitte */
            [++$number, '52°31′12.108″N|13°24′17.604″E', QueryParser::TYPE_SEARCH_COORDINATE], /* Berlin Mitte */
            [++$number, 'AIRP 52°31′12.108″N,13°24′17.604″E', QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES], /* Berlin Mitte */
            [++$number, 'AIRP: 52°31′12.108″N,13°24′17.604″E', QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES], /* Berlin Mitte */
            [++$number, 'AIRP:52°31′12.108″N,13°24′17.604″E', QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES], /* Berlin Mitte */
            [++$number, 'AIRP|AIRT 52°31′12.108″N,13°24′17.604″E', QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES], /* Berlin Mitte */
            [++$number, 'AIRP|AIRT: 52°31′12.108″N,13°24′17.604″E', QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES], /* Berlin Mitte */
            [++$number, 'AIRP|AIRT:52°31′12.108″N,13°24′17.604″E', QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES], /* Berlin Mitte */
            [++$number, 'Berlin Mitte', QueryParser::TYPE_SEARCH_LIST_GENERAL],
            [++$number, 'Der goldene Reiter', QueryParser::TYPE_SEARCH_LIST_GENERAL],



            /* Geoname search */
            [++$number, '12345678', QueryParser::TYPE_SEARCH_GEONAME_ID],
            [++$number, ' 12345678', QueryParser::TYPE_SEARCH_GEONAME_ID],
            [++$number, '       12345678', QueryParser::TYPE_SEARCH_GEONAME_ID],
            [++$number, '12345678    ', QueryParser::TYPE_SEARCH_GEONAME_ID],
            [++$number, '     12345678      ', QueryParser::TYPE_SEARCH_GEONAME_ID],



            /* Coordinate search (decimal) */
            [++$number, '52.524889,13.3692797', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '47.35858,8.530299', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '28.137008,-15.438614', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '52.524889, 13.3692797', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '47.35858, 8.530299', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '28.137008, -15.438614', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '     52.524889, 13.3692797', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '47.35858, 8.530299     ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '     28.137008,    -15.438614   ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '     -12.073136,   -77.167578   ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '52.524889 13.3692797', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '47.35858 8.530299', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '  28.137008    -15.438614 ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '52.524889|13.3692797', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '47.35858|8.530299', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '  28.137008  |  -15.438614 ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '  28.137008  /  -15.438614 ', QueryParser::TYPE_SEARCH_COORDINATE],

            /* Coordinate search (dms) */
            [++$number, '52°31′12.108″N,13°24′17.604″E', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '   52°31′12.108″N,13°24′17.604″E    ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '   52°31′12.108″N   ,    13°24′17.604″E    ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '   52°31′12.108″N       13°24′17.604″E    ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '   52°31′12.108″N   |    13°24′17.604″E    ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '   52°31′12.108″N   /    13°24′17.604″E    ', QueryParser::TYPE_SEARCH_COORDINATE],

            /* Coordinate search (mixed) */
            [++$number, '   28.137008       13°24′17.604″E    ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '   52°31′12.108″N       -15.438614    ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '   52°31′12.108″N   ,    -15.438614    ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '   52°31′12.108″N   |    -15.438614    ', QueryParser::TYPE_SEARCH_COORDINATE],
            [++$number, '   52°31′12.108″N   /    -15.438614    ', QueryParser::TYPE_SEARCH_COORDINATE],



            /* List search */
            [++$number, 'Dresden', QueryParser::TYPE_SEARCH_LIST_GENERAL],
            [++$number, 'Dresden Flughafen', QueryParser::TYPE_SEARCH_LIST_GENERAL],

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
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES,
                latitude: 52.52003,
                longitude: 13.40489,
                featureCodes: ['AIRP'],
            )], /* Berlin-Mitte */

            [++$number, 'AIRP: 52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES,
                latitude: 52.52003,
                longitude: 13.40489,
                featureCodes: ['AIRP'],
            )], /* Berlin-Mitte */

            [++$number, 'AIRP:52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES,
                latitude: 52.52003,
                longitude: 13.40489,
                featureCodes: ['AIRP'],
            )], /* Berlin-Mitte */

            [++$number, 'AIRP|AIRT 52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES,
                latitude: 52.52003,
                longitude: 13.40489,
                featureCodes: ['AIRP', 'AIRT'],
            )], /* Berlin-Mitte */

            [++$number, 'AIRP|AIRT: 52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES,
                latitude: 52.52003,
                longitude: 13.40489,
                featureCodes: ['AIRP', 'AIRT'],
            )], /* Berlin-Mitte */

            [++$number, 'AIRP|AIRT:52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES,
                latitude: 52.52003,
                longitude: 13.40489,
                featureCodes: ['AIRP', 'AIRT'],
            )], /* Berlin-Mitte */

            [++$number, 'S:52°31′12.108″N,13°24′17.604″E', QueryParser::getDataContainer(
                QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES,
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
