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
        'germany-potsdam-cecilienhof' => PlaceGermany::GERMANY_POTSDAM_CECILIENHOF_PALACE,
        'potsdam-cecilienhof' => PlaceGermany::GERMANY_POTSDAM_CECILIENHOF_PALACE,
        'cecilienhof' => PlaceGermany::GERMANY_POTSDAM_CECILIENHOF_PALACE,
        'germany-doebeln-blumenstrasse' => PlaceGermany::GERMANY_DOEBELN_BLUMENSTRASSE,
    ];
}
