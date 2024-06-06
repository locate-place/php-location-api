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

namespace App\Constants\Place;

/**
 * Class SearchTest
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-04-01)
 * @since 0.1.0 (2024-04-01) First version.
 */
class SearchTest
{
    final public const VALUES = [
        'ch-zuerich-friesenberg' => PlaceCH::ZUERICH_FRIESENBERG,
        'de-berlin-fernsehturm' => PlaceDE::BERLIN_FERNSEHTURM,
        'de-doebeln-blumenstrasse' => PlaceDE::DOEBELN_BLUMENSTRASSE,
        'de-dresden-frauenkirche' => PlaceDE::DRESDEN_FRAUENKIRCHE,
        'de-potsdam-cecilienhof' => PlaceDE::POTSDAM_CECILIENHOF_PALACE,
        'de-cologne-cologne-cathedral' => PlaceDE::COLOGNE_COLOGNE_CATHEDRAL,
        'gb-amesbury-stonehenge' => PlaceGB::AMESBURY_STONEHENGE,
        'gb-edinburgh-leith' => PlaceGB::EDINBURGH_LEITH,
        'gb-london-tower-bridge' => PlaceGB::LONDON_TOWER_BRIDGE,
        'gb-oxford-summertown' => PlaceGB::OXFORD_SUMMERTOWN,
        'gb-warwick-university' => PlaceGB::WARWICK_UNIVERSITY,
        'mt-floriana-knisja-ta-san-publiju' => PlaceMT::FLORIANA_KNISJA_TA_SAN_PUBLIJU,
        'mt-valletta-misrah-san-gorg' => PlaceMT::VALLETTA_MISRAH_SAN_GORG,
        'mt-victoria-il-katidral-ta-ghawdex' => PlaceMT::VICTORIA_IL_KATIDRAL_TA_GHAWDEX,
        'se-ekeroe-drottningholm-castle' => PlaceSE::EKEROE_DROTTNINGHOLM_CASTLE,
        'se-lidingoe-boobooliving' => PlaceSE::LIDINGOE_BOOBOOLIVING,
        'se-stockholm-palace' => PlaceSE::STOCKHOLM_PALACE,
        'us-austin-east-cesar-chavez' => PlaceUS::AUSTIN_EAST_CESAR_CHAVEZ,
        'us-binghamton' => PlaceUS::BINGHAMTON,
        'us-houston-east-downtown' => PlaceUS::HOUSTON_EAST_DOWNTOWN,
        'us-new-york-brooklyn' => PlaceUS::NEW_YORK_BROOKLYN,
        'us-new-york-one-world' => PlaceUS::NEW_YORK_ONE_WORLD,
        'us-washington-dc-white-house' => PlaceUS::WASHINGTON_DC_WHITE_HOUSE,
    ];
}
