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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\ApiPlatform\OpenApiContext\Parameter;
use App\ApiPlatform\Route\FeatureCodeRoute;
use App\ApiPlatform\State\FeatureCodeProvider;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;

/**
 * Class FeatureClass
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-07)
 * @since 0.1.0 (2024-06-07) First version.
 */
#[ApiResource(
    routePrefix: '/feature',
)]
#[GetCollection(
    uriTemplate: '/code{._format}',
    openapiContext: [
        'summary' => FeatureCodeRoute::SUMMARY_GET_COLLECTION,
        'description' => FeatureCodeRoute::DESCRIPTION_GET_COLLECTION,
        'parameters' => [
            Parameter::CLASS_,
            Parameter::LOCALE,
        ],
        'tags' => [
            'Feature'
        ],
        'responses' => [
            '200' => [
                'description' => 'FeatureCode collection',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                '$ref' => "#/components/schemas/FeatureCode"
                            ]
                        ]
                    ]
                ]
            ]
        ],
    ],
    paginationEnabled: false,
    provider: FeatureCodeProvider::class
)]
class FeatureCode extends BasePublicResource
{
    protected string $code;

    protected string $codeName;

    protected string $class;

    protected string $className;

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return self
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodeName(): string
    {
        return $this->codeName;
    }

    /**
     * @param string $codeName
     * @return self
     */
    public function setCodeName(string $codeName): self
    {
        $this->codeName = $codeName;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     * @return self
     */
    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @param string $className
     * @return self
     */
    public function setClassName(string $className): self
    {
        $this->className = $className;

        return $this;
    }
}
