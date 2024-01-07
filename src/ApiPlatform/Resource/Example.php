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

use ApiPlatform\Metadata\GetCollection;
use App\ApiPlatform\Route\ExampleRoute;
use App\ApiPlatform\State\ExampleProvider;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;

/**
 * Class Examples
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */

/* Get resources via geoname id: /api/v1/example */
#[GetCollection(
    openapiContext: [
        'description' => ExampleRoute::DESCRIPTION_COLLECTION_GET,
        'parameters' => [

        ],
    ],
    provider: ExampleProvider::class
)]
class Example extends BasePublicResource
{
    /** @var array<int|string, mixed> $place */
    private array $place;

    /**
     * Gets the example (place).
     *
     * @return array<int|string, mixed>
     */
    public function getPlace(): array
    {
        return $this->place;
    }

    /**
     * Sets the example (place).
     *
     * @param array<int|string, mixed> $place
     * @return self
     */
    public function setPlace(array $place): self
    {
        $this->place = $place;

        return $this;
    }
}
