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

use ApiPlatform\Metadata\Get;
use App\ApiPlatform\OpenApiContext\Parameter;
use App\ApiPlatform\Route\ApiKeyRoute;
use App\ApiPlatform\State\ApiKeyProvider;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;

/**
 * Class ApiKey
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-11)
 * @since 0.1.0 (2024-06-11) First version.
 */
#[Get(
    openapiContext: [
        'summary' => ApiKeyRoute::SUMMARY_GET,
        'description' => ApiKeyRoute::DESCRIPTION_GET,
        'parameters' => [
            Parameter::API_KEY,
        ],
        'responses' => [
            '200' => [
                'description' => 'ApiKey resource',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => "#/components/schemas/ApiKey"
                        ]
                    ]
                ]
            ]
        ]
    ],
    provider: ApiKeyProvider::class
)]
class ApiKey extends BasePublicResource
{
    private bool $isEnabled;

    private bool $isPublic;


    private bool $hasIpLimit;

    private bool $hasCredentialLimit;

    /**
     * @return bool
     */
    public function isIsEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @param bool $isEnabled
     * @return self
     */
    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIsPublic(): bool
    {
        return $this->isPublic;
    }

    /**
     * @param bool $isPublic
     * @return self
     */
    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHasIpLimit(): bool
    {
        return $this->hasIpLimit;
    }

    /**
     * @param bool $hasIpLimit
     * @return self
     */
    public function setHasIpLimit(bool $hasIpLimit): self
    {
        $this->hasIpLimit = $hasIpLimit;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHasCredentialLimit(): bool
    {
        return $this->hasCredentialLimit;
    }

    /**
     * @param bool $hasCredentialLimit
     * @return self
     */
    public function setHasCredentialLimit(bool $hasCredentialLimit): self
    {
        $this->hasCredentialLimit = $hasCredentialLimit;

        return $this;
    }
}
