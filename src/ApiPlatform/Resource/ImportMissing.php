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

namespace App\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\ApiPlatform\Route\ImportMissingRoute;
use App\ApiPlatform\State\ImportMissingProvider;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;

/**
 * Class ImportMissing
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-05)
 * @since 0.1.0 (2024-06-05) First version.
 */
#[ApiResource(
    uriTemplate: '/import',
)]
#[GetCollection(
    uriTemplate: 'import/missing.{_format}',
    openapiContext: [
        'summary' => ImportMissingRoute::SUMMARY_GET_COLLECTION,
        'description' => ImportMissingRoute::DESCRIPTION_GET_COLLECTION,
        'parameters' => [],
        'tags' => [
            'Import'
        ],
        'responses' => [
            '200' => [
                'description' => 'ImportMissing collection',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                '$ref' => "#/components/schemas/ImportMissing"
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    paginationEnabled: false,
    provider: ImportMissingProvider::class
)]
class ImportMissing extends BasePublicResource
{
    protected string $country;

    protected string $countryCode;

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return self
     */
    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * @param string $countryCode
     * @return self
     */
    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }
}
