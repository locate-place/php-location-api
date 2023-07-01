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
use App\Entity\Location as LocationEntity;
use App\Repository\LocationRepository;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\Wrapper\BaseResourceWrapperProvider;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LocationProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 */
final class LocationProvider extends BaseResourceWrapperProvider
{
    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     * @param LocationRepository $locationRepository
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected LocationRepository $locationRepository
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
     * @return Location
     */
    private function getLocation(LocationEntity $location): Location
    {
        return (new Location())
            ->setGeonameId($location->getGeonameId() ?: 0)
            ->setName($location->getName() ?: '')
            ->setFeature([
                'class' => $location->getFeatureClass()?->getClass() ?: '',
                'code' => $location->getFeatureCode()?->getCode() ?: '',
            ])
            ->setCoordinate([
                'latitude' => $location->getCoordinate()?->getLatitude() ?: .0,
                'longitude' => $location->getCoordinate()?->getLongitude() ?: .0,
            ])
        ;
    }

    /**
     * Returns a collection of location resources that matches the given coordinate.
     *
     * @return BasePublicResource[]
     */
    private function doProvideGetCollection(): array
    {
        //$coordinate = $this->getFilter('coordinate');

        $locations = [];

        foreach ([182559, 182560] as $geonameId) {
            $location = $this->locationRepository->find($geonameId);

            if (!$location instanceof LocationEntity) {
                continue;
            }

            $locations[] = $this->getLocation($location);
        }

        return $locations;
    }

    /**
     * Returns a location resource that matches the given coordinate.
     *
     * @return BasePublicResource
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    private function doProvideGet(): BasePublicResource
    {
        $geonameId = $this->getUriInteger(Name::GEONAME_ID);

        $location = $this->locationRepository->find($geonameId);

        if (!$location instanceof LocationEntity) {
            $this->setError(sprintf('Unable to find location with geoname id %d', $geonameId));
            return $this->getEmptyLocation($geonameId);
        }

        return $this->getLocation($location);
    }

    /**
     * Do the provided job.
     *
     * @return BasePublicResource|BasePublicResource[]
     * @throws ArrayKeyNotFoundException
     * @throws CaseUnsupportedException
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
