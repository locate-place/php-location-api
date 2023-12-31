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
use App\ApiPlatform\Resource\Location as LocationResource;
use App\ApiPlatform\Route\LocationRoute;
use App\ApiPlatform\State\Base\BaseProvider;
use App\Constants\DB\Distance;
use App\Constants\DB\Limit;
use App\Constants\Language\LocaleCode;
use App\Repository\LocationRepository;
use App\Service\LocationService;
use Doctrine\ORM\NonUniqueResultException;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\Wrapper\BaseResourceWrapperProvider;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
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
        $coordinate = $this->getFilterString(Name::COORDINATE);
        $limit = $this->hasFilter(Name::LIMIT) ? $this->getFilterInteger(Name::LIMIT) : Limit::LIMIT_10;
        $distance = $this->hasFilter(Name::DISTANCE) ? $this->getFilterInteger(Name::DISTANCE) : Distance::DISTANCE_1000;
        $featureClass = $this->hasFilter(Name::FEATURE_CLASS) ? $this->getFilterString(Name::FEATURE_CLASS) : null;

        $locations = $this->locationService->getLocationsByCoordinate(
            new Coordinate($coordinate),
            $limit,
            $distance,
            $featureClass
        );

        if ($this->locationService->hasError()) {
            $this->setError($this->locationService->getError());
        }

        return $locations;
    }

    /**
     * Returns a location resource that matches the given geoname id.
     *
     * @return BasePublicResource
     * @throws ArrayKeyNotFoundException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     */
    private function doProvideGetWithGeonameId(): BasePublicResource
    {
        $geonameId = $this->getUriInteger(Name::GEONAME_ID);

        $location = $this->locationService->getLocationByGeonameId($geonameId);

        if ($this->locationService->hasError()) {
            $this->setError($this->locationService->getError());
        }

        return $location;
    }

    /**
     * Returns a location resource that matches the given coordinate.
     *
     * @return BasePublicResource
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    private function doProvideGetWithCoordinate(): BasePublicResource
    {
        /* Check given coordinate. */
        $coordinate = $this->getCoordinateByFilter();
        if (is_null($coordinate)) {
            $this->setError('No coordinate given.');
            return (new LocationResource())
                ->setGeonameId(0)
            ;
        }

        /* Check locale */
        $isoLanguage = $this->getIsoLanguageByFilter();
        $country = $this->getCountryByFilter();
        $locale = $this->getLocaleByFilter($isoLanguage, $country);
        if (!in_array($locale, LocaleCode::ALL)) {
            $this->setError(sprintf('Locale "%s" is not supported yet. Please choose on of them: %s', $locale, implode(', ', LocaleCode::ALL)));
            return (new LocationResource())->setGeonameId(0);
        }

        /* Collect some url parameters. */
        $nextPlaces = $this->isNextPlacesByFilter();

        $location = $this->locationService->getLocationByCoordinate(
            $coordinate,
            $isoLanguage,
            $country,
            $nextPlaces
        );

        if ($this->locationService->hasError()) {
            $this->setError($this->locationService->getError());
        }

        return $location;
    }

    /**
     * Do the provided job.
     *
     * @return BasePublicResource|BasePublicResource[]
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function doProvide(): BasePublicResource|array
    {
        return match(true) {
            $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION => $this->doProvideGetCollection(),
            $this->getRequestMethod() === Request::METHOD_GET && $this->hasFilter(Name::COORDINATE) => $this->doProvideGetWithCoordinate(),
            $this->getRequestMethod() === Request::METHOD_GET && $this->hasUri(Name::GEONAME_ID) => $this->doProvideGetWithGeonameId(),
            default => throw new CaseUnsupportedException('Unsupported mode from api endpoint /api/v1/location.'),
        };
    }
}
