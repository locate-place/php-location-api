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

namespace App\ApiPlatform\State;

use App\ApiPlatform\OpenApiContext\Name;
use App\ApiPlatform\Resource\Location;
use App\ApiPlatform\Route\LocationRoute;
use App\ApiPlatform\State\Base\BaseProvider;
use App\Entity\Location as LocationEntity;
use App\Repository\LocationRepository;
use App\Service\LocationService;
use Doctrine\ORM\NonUniqueResultException;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\Wrapper\BaseResourceWrapperProvider;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class LocationProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 */
final class LocationProvider extends BaseProvider
{
    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     * @param LocationRepository $locationRepository
     * @param TranslatorInterface $translator
     * @param LocationService $locationService
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected LocationRepository $locationRepository,
        protected TranslatorInterface $translator,
        protected LocationService $locationService
    )
    {
        parent::__construct($version, $parameterBag, $request);
    }

    /**
     * Returns the route properties according to current class.
     *
     * @return array<string, array<string, int|string|string[]>>
     */
    protected function getRouteProperties(): array
    {
        return LocationRoute::PROPERTIES;
    }

    /**
     * Converts the given Location entities to ApiPlatform resources.
     *
     * @param array<int, LocationEntity> $locationEntities
     * @param Coordinate $coordinate
     * @return array<int, Location>
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    private function convertLocationEntityToLocation(array $locationEntities, Coordinate $coordinate): array
    {
        $locations = [];

        foreach ($locationEntities as $locationEntity) {
            if (!$locationEntity instanceof LocationEntity) {
                continue;
            }

            $locations[] = $this->getLocation($locationEntity, $coordinate);
        }

        return $locations;
    }

    /**
     * Returns an empty Location entity.
     *
     * @param int $geonameId
     * @return Location
     */
    private function getEmptyLocation(int $geonameId): Location
    {
        return (new Location())
            ->setGeonameId($geonameId)
        ;
    }

    /**
     * Returns a Location entity.
     *
     * @param LocationEntity $location
     * @param Coordinate|null $coordinate
     * @return Location
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getLocation(LocationEntity $location, Coordinate|null $coordinate): Location
    {
        $featureClass = $location->getFeatureClass()?->getClass() ?: '';
        $featureClassName = $this->translator->trans(
            $featureClass,
            domain: 'feature-code',
            locale: 'de_DE'
        );

        $featureCode = $location->getFeatureCode()?->getCode() ?: '';
        $featureCodeName = $this->translator->trans(
            sprintf('%s.%s', $featureClass, $featureCode),
            domain: 'place',
            locale: 'de_DE'
        );

        $latitude = $location->getCoordinate()?->getLatitude() ?: .0;
        $longitude = $location->getCoordinate()?->getLongitude() ?: .0;

        $coordinateTarget = new Coordinate($latitude, $longitude);

        $distance = is_null($coordinate) ? null : [
            'meters' => $coordinate->getDistance($coordinateTarget),
            'kilometers' => $coordinate->getDistance($coordinateTarget, Coordinate::RETURN_KILOMETERS),
        ];

        $direction = is_null($coordinate) ? null : [
            'degree' => $coordinate->getDegree($coordinateTarget),
            'direction' => $coordinate->getDirection($coordinateTarget),
        ];

        return (new Location())
            ->setGeonameId($location->getGeonameId() ?: 0)
            ->setName($location->getName() ?: '')
            ->setCountry([
                'code' => $location->getCountry()?->getCode() ?: '',
                'name' => $location->getCountry()?->getName() ?: '',
            ])
            ->setFeature([
                'class' => $featureClass,
                'class-name' => $featureClassName,
                'code' => $featureCode,
                'code-name' => $featureCodeName,
            ])
            ->setCoordinate([
                'latitude' => $latitude,
                'longitude' => $longitude,
                'distance' => $distance,
                'direction' => $direction,
            ])
        ;
    }

    /**
     * Returns the full location.
     *
     * @param LocationEntity $location
     * @param Coordinate|null $coordinateSource
     * @return Location
     * @throws CaseUnsupportedException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws ClassInvalidException
     */
    private function getLocationFull(LocationEntity $location, Coordinate|null $coordinateSource = null): Location
    {
        $latitude = $location->getCoordinate()?->getLatitude() ?: .0;
        $longitude = $location->getCoordinate()?->getLongitude() ?: .0;

        $coordinateTarget = new Coordinate($latitude, $longitude);

        $location = $this->getLocation($location, $coordinateSource)
            ->setLink([
                'google' => $coordinateTarget->getLinkGoogle(),
                'openstreetmap' => $coordinateTarget->getLinkOpenStreetMap(),
            ])
        ;

        $locationsP = $this->locationRepository->findAdminLocationsByCoordinate($coordinateTarget, null, 25);
        $locationInformation = $this->locationService->getLocationInformation($locationsP);

        if (!is_null($locationInformation)) {
            $location
                ->setLocation($locationInformation)
            ;
        }

        return $location;
    }

    /**
     * Returns a collection of location resources that matches the given coordinate.
     *
     * @return BasePublicResource[]
     * @throws ArrayKeyNotFoundException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    private function doProvideGetCollection(): array
    {
        $coordinate = new Coordinate($this->getFilterString(Name::COORDINATE));
        $distance = $this->hasFilter(Name::DISTANCE) ? $this->getFilterInteger(Name::DISTANCE) : 1000;
        $featureClass = $this->hasFilter(Name::FEATURE_CLASS) ? $this->getFilterString(Name::FEATURE_CLASS) : null;

        $locationEntities = $this->locationRepository->findLocationsByCoordinate(
            $coordinate,
            $distance,
            $featureClass
        );

        return $this->convertLocationEntityToLocation($locationEntities, $coordinate);
    }

    /**
     * Returns a location resource that matches the given coordinate.
     *
     * @return BasePublicResource
     * @throws ArrayKeyNotFoundException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    private function doProvideGet(): BasePublicResource
    {
        $geonameId = $this->getUriInteger(Name::GEONAME_ID);

        $location = $this->locationRepository->findOneBy(['geonameId' => $geonameId]);

        if (!$location instanceof LocationEntity) {
            $this->setError(sprintf('Unable to find location with geoname id %d', $geonameId));
            return $this->getEmptyLocation($geonameId);
        }

        return $this->getLocationFull($location);
    }

    /**
     * Do the provided job.
     *
     * @return BasePublicResource|BasePublicResource[]
     * @throws ArrayKeyNotFoundException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function doProvide(): BasePublicResource|array
    {
        return match($this->getRequestMethod()) {
            BaseResourceWrapperProvider::METHOD_GET_COLLECTION => $this->doProvideGetCollection(),
            Request::METHOD_GET => $this->doProvideGet(),
            default => throw new CaseUnsupportedException('Unsupported mode from api endpoint /api/v1/location.'),
        };
    }
}
