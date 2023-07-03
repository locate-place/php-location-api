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

namespace App\ApiPlatform\State\Base;

use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\Wrapper\BaseResourceWrapperProvider;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Type\TypeInvalidException;

/**
 * Class BaseProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-04)
 * @since 0.1.0 (2023-07-04) First version.
 */
class BaseProvider extends BaseResourceWrapperProvider
{
    /**
     * Extends the getUriVariablesOutput method.
     *
     * @return array<int|string, array<string, array<string, string>|string>|bool|int|string>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    protected function getUriVariablesOutput(): array
    {
        $uriVariablesOutput = parent::getUriVariablesOutput();

        if (array_key_exists('coordinate', $uriVariablesOutput)) {
            $coordinate = (string) $uriVariablesOutput['coordinate'];

            $coordinateInstance = new Coordinate($coordinate);

            $uriVariablesOutput['coordinate'] = [
                'raw' => $coordinate,
                'parsed' => [
                    'latitude' => (string) $coordinateInstance->getLatitude(),
                    'longitude' => (string) $coordinateInstance->getLongitude(),
                    'latitudeDms' => $coordinateInstance->getLatitudeDMS(),
                    'longitudeDms' => $coordinateInstance->getLongitudeDMS(),
                ]
            ];
        }

        return $uriVariablesOutput;
    }
}
