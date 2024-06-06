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

use App\Constants\Key\KeyArray;
use App\Constants\Language\CountryCode;
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
        'ch-rheinfall' => [
            KeyArray::GEONAME_ID => 2_659_061,
            KeyArray::NAME => 'Rheinfall',
            'location' => [
                'district-locality' => 'Laufen',
                'city-municipality' => 'Laufen',
                'state' => 'Zürich',
                'country' => 'Schweiz'
            ],
            'country' => CountryCode::CH,
        ],
        'de-berlin-fernsehturm' => [
            KeyArray::GEONAME_ID => 6_325_497,
            KeyArray::NAME => 'Berlin - Fernsehturm',
            'location' => [
                'district-locality' => 'Mitte',
                'city-municipality' => 'Berlin',
                'state' => 'Berlin',
                'country' => 'Deutschland'
            ],
            'country' => CountryCode::DE,
        ],
        'de-dresden-frauenkirche' => [
            KeyArray::GEONAME_ID => 6_543_921,
            KeyArray::NAME => 'Dresden - Frauenkirche',
            'location' => [
                'district-locality' => 'Innere Altstadt',
                'city-municipality' => 'Dresden',
                'state' => 'Sachsen',
                'country' => 'Deutschland',
            ],
            'country' => CountryCode::DE,
        ],
        'de-potsdam-cecilienhof' => [
            KeyArray::GEONAME_ID => 6_488_416,
            KeyArray::NAME => 'Potsdam - Cecilienhof',
            'location' => [
                'district-locality' => 'Nauener Vorstadt',
                'city-municipality' => 'Potsdam',
                'state' => 'Brandenburg',
                'country' => 'Deutschland',
            ],
            'country' => CountryCode::DE,
        ],
        'de-cologne-cologne-cathedral' => [
            KeyArray::GEONAME_ID => 6_324_464,
            KeyArray::NAME => 'Köln - Kölner Dom',
            'location' => [
                'district-locality' => 'Altstadt Nord',
                'city-municipality' => 'Köln',
                'state' => 'Nordrhein-Westfalen',
                'country' => 'Deutschland',
            ],
            'country' => CountryCode::DE,
        ],
        'de-neuschwanstein' => [
            KeyArray::GEONAME_ID => 2_864_198,
            KeyArray::NAME => 'Neuschwanstein',
            'location' => [
                'district-locality' => 'Hohenschwangau',
                'city-municipality' => 'Schwangau',
                'state' => 'Bayern',
                'country' => 'Deutschland',
            ],
            'country' => CountryCode::DE,
        ],
        'fr-mont-blanc' => [
            KeyArray::GEONAME_ID => 3_181_986,
            KeyArray::NAME => 'Mont Blanc',
            'location' => [
                'district-locality' => 'Taconnaz',
                'city-municipality' => 'Les Houches',
                'state' => 'Auvergne-Rhône-Alpes',
                'country' => ' Frankreich',
            ],
            'country' => CountryCode::FR,
        ],
        'gb-amesbury-stonehenge' => [
            KeyArray::GEONAME_ID => 2_636_812,
            KeyArray::NAME => 'Amesbury - Stonehenge',
            'location' => [
                'district-locality' => 'Countess',
                'city-municipality' => 'Amesbury',
                'state' => 'England',
                'country' => 'UK',
            ],
            'country' => CountryCode::GB,
        ],
        'gb-london-tower-bridge' => [
            KeyArray::GEONAME_ID => 2_635_595,
            KeyArray::NAME => 'London - Tower Bridge',
            'location' => [
                'district-locality' => 'London City',
                'city-municipality' => 'London',
                'state' => 'England',
                'country' => 'UK',
            ],
            'country' => CountryCode::GB,
        ],
        'it-coliseum' => [
            KeyArray::GEONAME_ID => 6_269_248,
            KeyArray::NAME => 'Kolosseum',
            'location' => [
                'city-municipality' => 'Rom',
                'state' => 'Latium',
                'country' => ' Italien',
            ],
            'country' => CountryCode::IT,
        ],
        'it-piazza-san-marco' => [
            KeyArray::GEONAME_ID => 3_229_870,
            KeyArray::NAME => 'Markusplatz',
            'location' => [
                'city-municipality' => 'Venedig',
                'state' => 'Venetien',
                'country' => ' Italien',
            ],
            'country' => CountryCode::IT,
        ],
        'mt-valletta-misrah-san-gorg' => [
            KeyArray::GEONAME_ID => 2_563_640,
            KeyArray::NAME => 'Valletta - Misrah San Gorg',
            'location' => [
                'city-municipality' => 'Valletta',
                'state' => 'Southern Harbour District',
                'country' => 'Malta',
            ],
            'country' => CountryCode::MT,
        ],
        'se-ekeroe-drottningholm-castle' => [
            KeyArray::GEONAME_ID => 2_717_199,
            KeyArray::NAME => 'Ekerö - Drottningholm Palace',
            'location' => [
                'district-locality' => 'Drottningholm',
                'city-municipality' => 'Ekerö',
                'state' => 'Stockholm',
                'country' => 'Schweden',
            ],
            'country' => CountryCode::SE,
        ],
        'se-stockholm-palace' => [
            KeyArray::GEONAME_ID => 6_942_295,
            KeyArray::NAME => 'Stockholm - Palace',
            'location' => [
                'district-locality' => 'Gamla Stan',
                'city-municipality' => 'Stockholm',
                'state' => 'Stockholm',
                'country' => 'Schweden',
            ],
            'country' => CountryCode::SE,
        ],
        'us-new-york-brooklyn' => [
            KeyArray::GEONAME_ID => 5_110_306,
            KeyArray::NAME => 'Brooklyn Bridge',
            'location' => [
                'district-locality' => 'Fulton Ferry',
                'borough-locality' => 'Brooklyn',
                'city-municipality' => 'New York City',
                'state' => 'New York',
                'country' => 'Vereinigte Staaten',
            ],
            'country' => CountryCode::US,
        ],
        'us-new-york-one-world' => [
            KeyArray::GEONAME_ID => 8_015_460,
            KeyArray::NAME => 'One World Trade Center',
            'location' => [
                'district-locality' => 'Battery Park City',
                'borough-locality' => 'Manhattan',
                'city-municipality' => 'New York City',
                'state' => 'New York',
                'country' => 'Vereinigte Staaten',
            ],
            'country' => CountryCode::US,
        ],
        'us-washington-dc-white-house' => [
            KeyArray::GEONAME_ID => 9_675_434,
            KeyArray::NAME => 'Washington DC - White House',
            'location' => [
                'district-locality' => 'Franklin McPherson Square',
                'city-municipality' => 'Washington',
                'state' => 'Washington, D.C.',
                'country' => 'Vereinigte Staaten',
            ],
            'country' => CountryCode::US,
        ],
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
