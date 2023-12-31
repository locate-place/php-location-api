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

namespace App\ApiPlatform\Resource\Base;

use App\Constants\DB\FeatureClass;
use App\Constants\Key\KeyArray;
use App\Constants\Path\Path;
use App\DataTypes\Base\DataType;
use App\DataTypes\Coordinate;
use App\DataTypes\Feature;
use App\DataTypes\Links;
use App\DataTypes\Locations;
use App\DataTypes\NextPlaces;
use App\DataTypes\Properties;
use App\DataTypes\Timezone;
use DateTimeImmutable;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use LogicException;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Class LocationBase
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-30)
 * @since 0.1.0 (2023-12-30) First version.
 */
abstract class LocationBase extends BasePublicResource
{
    #[SerializedName('geoname-id')]
    private int $geonameId;

    #[SerializedName('name')]
    private string $name;

    #[SerializedName('name-full')]
    private string $nameFull;

    #[SerializedName('updated-at')]
    private DateTimeImmutable $updatedAt;


    #[SerializedName('properties')]
    private Properties $properties;


    #[SerializedName('feature')]
    private Feature $feature;


    #[SerializedName('coordinate')]
    private Coordinate $coordinate;


    #[SerializedName('timezone')]
    private Timezone $timezone;


    #[SerializedName('links')]
    private Links $links;


    #[SerializedName('locations')]
    private Locations $locations;

    #[SerializedName('next-places')]
    private NextPlaces $nextPlaces;

    /**
     * Gets the geoname ID.
     *
     * @return int
     */
    public function getGeonameId(): int
    {
        return $this->geonameId;
    }

    /**
     * Sets the geoname ID.
     *
     * @param int $geonameId
     * @return self
     */
    public function setGeonameId(int $geonameId): self
    {
        $this->geonameId = $geonameId;

        return $this;
    }

    /**
     * Gets the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name.
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getNameFull(): string
    {
        return $this->nameFull;
    }

    /**
     * @param string $nameFull
     * @return self
     */
    public function setNameFull(string $nameFull): self
    {
        $this->nameFull = $nameFull;

        return $this;
    }

    /**
     * Gets the updated at date.
     *
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Sets the updated at date.
     *
     * @param DateTimeImmutable $updatedAt
     * @return $this
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt): LocationBase
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Gets the properties.
     *
     * @return Properties
     */
    public function getProperties(): Properties
    {
        return $this->properties;
    }

    /**
     * Sets the properties.
     *
     * @param Properties $properties
     * @return $this
     */
    public function setProperties(Properties $properties): LocationBase
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * Gets the feature.
     *
     * @return Feature
     */
    public function getFeature(): Feature
    {
        return $this->feature;
    }

    /**
     * Sets the feature.
     *
     * @param Feature $feature
     * @return self
     */
    public function setFeature(Feature $feature): self
    {
        $this->feature = $feature;

        return $this;
    }

    /**
     * Gets the coordinate array.
     *
     * @return Coordinate
     */
    public function getCoordinate(): Coordinate
    {
        return $this->coordinate;
    }

    /**
     * Sets the coordinate array.
     *
     * @param Coordinate $coordinate
     * @return self
     */
    public function setCoordinate(Coordinate $coordinate): self
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * @return Timezone
     */
    public function getTimezone(): Timezone
    {
        return $this->timezone;
    }

    /**
     * @param Timezone $timezone
     * @return self
     */
    public function setTimezone(Timezone $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Gets the link array.
     *
     * @return Links
     */
    public function getLinks(): Links
    {
        return $this->links;
    }

    /**
     * Sets the link array.
     *
     * @param Links $links
     * @return self
     */
    public function setLinks(Links $links): self
    {
        $this->links = $links;

        return $this;
    }

    /**
     * Adds a link to array.
     *
     * @param string[]|string $path
     * @param string $value
     * @param int|null $number
     * @return void
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function addLink(array|string $path, string $value, int|null $number = null): void
    {
        $path = is_string($path) ? [$path] : $path;

        if (count($path) <= 0) {
            throw new LogicException('Path must contain at least one element');
        }

        if (!isset($this->links)) {
            $this->links = new Links();
        }

        $linkArray = $this->getLinks()->getArray();

        $loop = &$linkArray;

        foreach ($path as $key) {
            if (!is_array($loop)) {
                throw new LogicException('Loop variable must be an array.');
            }

            if (!array_key_exists($key, $loop)) {
                $loop[$key] = [];
            }

            $loop = &$loop[$key];
        }

        match ($number) {
            null => $loop = $value,
            default => $loop[] = [
                KeyArray::LINK => $value,
                KeyArray::NUMBER => $number,
            ],
        };

        $this->links = new Links($linkArray);
    }

    /**
     * Returns the Locations container.
     *
     * @return Locations
     */
    public function getLocations(): Locations
    {
        return $this->locations;
    }

    /**
     * Sets the Locations container.
     *
     * @param Locations $locations
     * @return self
     */
    public function setLocations(Locations $locations): self
    {
        $this->locations = $locations;

        return $this;
    }

    /**
     * Returns the NextPlaces container.
     *
     * @return NextPlaces
     */
    public function getNextPlaces(): NextPlaces
    {
        return $this->nextPlaces;
    }

    /**
     * Sets the NextPlaces container.
     *
     * @param NextPlaces $nextPlaces
     * @return $this
     */
    public function setNextPlaces(NextPlaces $nextPlaces): self
    {
        $this->nextPlaces = $nextPlaces;

        return $this;
    }

    /**
     * Sets the full name.
     *
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function setNameFullFromLocationResource(): void
    {
        $locations = $this->getLocations();

        $nameFull = [];

        foreach (['district-locality', 'city-municipality', 'state', 'country'] as $key) {
            $path = [$key, KeyArray::NAME];
            if ($locations->hasKey($path)) {
                $nameFull[] = $locations->getKeyString($path);
            }
        }

        $this->setNameFull(implode(', ', $nameFull));
    }

    /**
     * Sets all sub links to the main part.
     *
     * @return void
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     */
    public function setMainWikipediaLinks(): void
    {
        /* Add wikipedia links from locations */
        $this->addMainWikipediaLinks($this->getLocations(), [KeyArray::LOCATIONS]);

        foreach (FeatureClass::FEATURE_CLASSES_ALL as $featureClass) {
            $this->addMainWikipediaLinks(
                $this->getNextPlaces(),
                [KeyArray::NEXT_PLACES, $featureClass],
                [$featureClass, KeyArray::PLACES]
            );
        }
    }

    /**
     * Adds all wikipedia links to the main links.
     *
     * @param DataType $dataType
     * @param string|string[] $pathOutput
     * @param int|string|array<int, mixed> $pathSource
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function addMainWikipediaLinks(
        DataType $dataType,
        string|array $pathOutput,
        int|string|array $pathSource = [],
    ): void
    {
        $pathOutput = is_string($pathOutput) ? [$pathOutput] : $pathOutput;

        if (!$dataType->hasKey($pathSource)) {
            return;
        }

        $data = $dataType->getKeyArray($pathSource);

        $isList = array_is_list($data);

        foreach ($data as $key => $location) {

            if (!is_array($location)) {
                throw new LogicException(sprintf('Location data must be an array. Key: %s', $key));
            }

            /* No single links found to add to main links chapter. */
            if (!array_key_exists(KeyArray::LINKS, $location)) {
                continue;
            }

            $links = $location[KeyArray::LINKS];

            if (!$links instanceof Links) {
                continue;
            }

            if (!$links->hasKey(Path::WIKIPEDIA_THIS)) {
                continue;
            }

            $path = array_merge([KeyArray::WIKIPEDIA], $pathOutput);

            if (!$isList) {
                $path[] = (string) $key;
            }

            $this->addLink(
                $path,
                $links->getKeyString(Path::WIKIPEDIA_THIS),
                $isList ? (int) $key : null
            );
        }
    }
}
