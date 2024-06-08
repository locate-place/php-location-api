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

namespace App\ApiPlatform\State;

use App\ApiPlatform\Resource\FeatureClass;
use App\ApiPlatform\Route\FeatureClassRoute;
use App\ApiPlatform\State\Base\BaseProviderCustom;
use App\Constants\DB\FeatureClass as FeatureClassDb;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Type\TypeInvalidException;

/**
 * Class FeatureClassProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-07)
 * @since 0.1.0 (2024-06-07) First version.
 */
final class FeatureClassProvider extends BaseProviderCustom
{
    /**
     * Returns the route properties according to current class.
     *
     * @return array<string, array<string, int|string|string[]>>
     */
    protected function getRouteProperties(): array
    {
        return FeatureClassRoute::PROPERTIES;
    }

    /**
     * @return FeatureClass[]
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    private function doProvideGetCollection(): array
    {
        $locale = $this->getLocaleByFilter();

        $language = $this->getLanguageByLocale($locale);

        $featureClasses = [];

        foreach (FeatureClassDb::ALL as $class) {
            $featureClasses[] = (new FeatureClass())
                ->setClass($class)
                ->setClassName((new FeatureClassDb($this->translator, $language))->translate($class))
            ;
        }

        return $featureClasses;
    }

    /**
     * Do the provided job.
     *
     * @return FeatureClass[]
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function doProvide(): array
    {
        return $this->doProvideGetCollection();
    }
}
