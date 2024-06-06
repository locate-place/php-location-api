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

namespace App\ApiPlatform\Resource;

use ApiPlatform\Metadata\GetCollection;
use App\ApiPlatform\OpenApiContext\Parameter;
use App\ApiPlatform\Route\FeatureClassRoute;
use App\ApiPlatform\State\FeatureClassProvider;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;

/**
 * Class FeatureClass
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-07)
 * @since 0.1.0 (2024-06-07) First version.
 */
#[GetCollection(
    openapiContext: [
        'summary' => FeatureClassRoute::SUMMARY_GET_COLLECTION,
        'description' => FeatureClassRoute::DESCRIPTION_GET_COLLECTION,
        'parameters' => [
            Parameter::LOCALE,
        ],
    ],
    paginationEnabled: false,
    provider: FeatureClassProvider::class
)]
class FeatureClass extends BasePublicResource
{
    protected string $class;

    protected string $name;

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     * @return FeatureClass
     */
    public function setClass(string $class): FeatureClass
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return FeatureClass
     */
    public function setName(string $name): FeatureClass
    {
        $this->name = $name;
        return $this;
    }
}
