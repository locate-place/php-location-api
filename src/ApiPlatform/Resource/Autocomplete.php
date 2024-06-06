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
use App\ApiPlatform\Route\AutocompleteRoute;
use App\ApiPlatform\State\AutocompleteProvider;
use App\ApiPlatform\Type\AutocompleteFeature;
use App\ApiPlatform\Type\AutocompleteLocation;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Class Autocomplete
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-04-07)
 * @since 0.1.0 (2024-04-07) First version.
 */
#[Get(
    openapiContext: [
        'summary' => AutocompleteRoute::SUMMARY_GET,
        'description' => AutocompleteRoute::DESCRIPTION_GET,
        'parameters' => [
            Parameter::QUERY_AUTOCOMPLETE,
            Parameter::LANGUAGE,
        ],
        'responses' => [
            '200' => [
                'description' => 'Autocomplete resource',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => "#/components/schemas/Autocomplete"
                        ]
                    ]
                ]
            ]
        ]
    ],
    provider: AutocompleteProvider::class
)]
class Autocomplete extends BasePublicResource
{
    /** @var array<int, AutocompleteLocation> $locations */
    #[SerializedName('locations')]
    private array $locations;

    /** @var array<int, AutocompleteFeature> $featureClasses */
    #[SerializedName('feature-classes')]
    private array $featureClasses;

    /** @var array<int, AutocompleteFeature> $featureCodes */
    #[SerializedName('feature-codes')]
    private array $featureCodes;

    /**
     * @return array<int, AutocompleteLocation>
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * @param array<int, AutocompleteLocation> $locations
     * @return self
     */
    public function setLocations(array $locations): self
    {
        $this->locations = $locations;

        return $this;
    }

    /**
     * @return array<int, AutocompleteFeature>
     */
    public function getFeatureClasses(): array
    {
        return $this->featureClasses;
    }

    /**
     * @param array<int, AutocompleteFeature> $featureClasses
     * @return self
     */
    public function setFeatureClasses(array $featureClasses): self
    {
        $this->featureClasses = $featureClasses;

        return $this;
    }

    /**
     * @return array<int, AutocompleteFeature>
     */
    public function getFeatureCodes(): array
    {
        return $this->featureCodes;
    }

    /**
     * @param array<int, AutocompleteFeature> $featureCodes
     * @return self
     */
    public function setFeatureCodes(array $featureCodes): self
    {
        $this->featureCodes = $featureCodes;

        return $this;
    }
}
