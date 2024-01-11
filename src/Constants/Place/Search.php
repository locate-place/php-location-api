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

namespace App\Constants\Place;

use App\Constants\Key\KeyArray;
use LogicException;

/**
 * Class Search
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-14)
 * @since 0.1.0 (2023-08-14) First version.
 */
class Search
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

    final public const SEPARATOR_NAME_FULL = ', ';

    /**
     * @param string $key
     */
    public function __construct(private readonly string $key)
    {
    }

    /**
     * Returns any key value.
     *
     * @param string $key
     * @return mixed
     */
    private function getValue(string $key): mixed
    {
        if (!array_key_exists($this->key, self::VALUES)) {
            throw new LogicException(sprintf('Unable to find key "%s" in %s.', $this->key, self::class));
        }

        $search = self::VALUES[$this->key];

        if (!array_key_exists($key, $search)) {
            throw new LogicException(sprintf('Unable to find key "%s".', $key));
        }

        return $search[$key];
    }

    /**
     * Returns the geoname-id.
     *
     * @return int
     */
    public function getGeonameId(): int
    {
        $key = KeyArray::GEONAME_ID;

        $geonameId = $this->getValue($key);

        if (!is_int($geonameId)) {
            throw new LogicException(sprintf('Key "%s" must be an integer.', $key));
        }

        return $geonameId;
    }

    /**
     * Returns the name.
     *
     * @return string
     */
    public function getName(): string
    {
        $key = KeyArray::NAME;

        $name = $this->getValue($key);

        if (!is_string($name)) {
            throw new LogicException(sprintf('Key "%s" must be a string.', $key));
        }

        return $name;
    }

    /**
     * Returns the full name.
     *
     * @return string
     */
    public function getNameFull(): string
    {
        $key = KeyArray::LOCATION;

        $location = $this->getValue($key);

        if (!is_array($location)) {
            throw new LogicException(sprintf('Key "%s" must be an array.', $key));
        }

        $values = array_values($location);

        return implode(self::SEPARATOR_NAME_FULL, $values);
    }
}
