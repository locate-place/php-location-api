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

namespace App\Service;

use App\Constants\DB\FeatureClass;
use App\Entity\Location;
use App\Entity\Location as LocationEntity;
use App\Repository\AlternateNameRepository;
use App\Repository\LocationRepository;
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
use JetBrains\PhpStorm\NoReturn;
use JsonException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LocationServiceDebug
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class LocationServiceDebug
{
    final public const DEBUG_LIMIT = 1;

    protected const DEBUG_CAPTION = '%-9s | %-6s | %-12s | %-2s | %-11s | %-8s | %-8s | %-8s | %-8s | %-20s | %s';

    protected const DEBUG_CONTENT = '%9s | %-6s | %12s | %-2s | %11s | %8s | %8s | %8s | %8s | %-20s | %s';

    protected const SEPARATOR_LENGTH = 150;

    protected const LIMIT_ARRAY = 3;

    final public const TEXT_NOT_AVAILABLE = 'N/A';

    protected LocationContainer|null $locationContainer = null;

    protected int $debugLimit = self::DEBUG_LIMIT;

    protected OutputInterface $output;

    protected Coordinate $coordinate;

    protected float|null $timeStart = null;

    /**
     * @param LocationRepository $locationRepository
     * @param AlternateNameRepository $alternateNameRepository
     * @param LocationService $locationService
     * @param LocationServiceConfig $locationServiceConfig
     * @param LocationServiceAlternateName $locationServiceName
     */
    public function __construct(
        protected LocationRepository $locationRepository,
        protected AlternateNameRepository $alternateNameRepository,
        protected LocationService $locationService,
        protected LocationServiceConfig $locationServiceConfig,
        protected LocationServiceAlternateName $locationServiceName
    )
    {
    }

    /**
     * Starts the timer.
     *
     * @return void
     */
    public function startMeasurement(): void
    {
        $this->timeStart = microtime(true);
    }

    /**
     * @param int $debugLimit
     * @return self
     */
    public function setDebugLimit(int $debugLimit): self
    {
        $this->debugLimit = $debugLimit;

        return $this;
    }

    /**
     * @param OutputInterface $output
     * @return self
     */
    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    /**
     * @param Coordinate $coordinate
     * @return self
     */
    public function setCoordinate(Coordinate $coordinate): self
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * Sets location container.
     *
     * @param LocationContainer $locationContainer
     * @return void
     */
    public function setLocationContainer(LocationContainer $locationContainer): void
    {
        $this->locationContainer = $locationContainer;
    }

    /**
     * Returns the alternate name by given iso language.
     *
     * @param Location|null $location
     * @param string $isoLanguage
     * @return string
     */
    private function getNameByIsoLanguage(?Location $location, string $isoLanguage): string
    {
        return $this->locationServiceName->getNameByIsoLanguage($location, $isoLanguage);
    }

    /**
     * Prints some debug information.
     *
     * @param Location $locationSource
     * @param string $isoLanguage
     * @return void
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function printDebug(Location $locationSource, string $isoLanguage): void
    {
        $this->printPlace($locationSource, $isoLanguage);
        $this->printFeatureClass($locationSource, FeatureClass::P, $isoLanguage, 'city, village, ...');
        $this->printFeatureClass($locationSource, FeatureClass::A, $isoLanguage, 'country, state, region,...');
        $this->printFeatureClass($locationSource, FeatureClass::H, $isoLanguage, 'stream, lake, ...');
        $this->printFeatureClass($locationSource, FeatureClass::L, $isoLanguage, 'parks, area, ...');
        $this->printFeatureClass($locationSource, FeatureClass::R, $isoLanguage, 'road, railroad,...');
        $this->printFeatureClass($locationSource, FeatureClass::S, $isoLanguage, 'spot, building, farm,...');
        $this->printFeatureClass($locationSource, FeatureClass::T, $isoLanguage, 'mountain, hill, rock,...');
        $this->printFeatureClass($locationSource, FeatureClass::U, $isoLanguage, 'undersea');
        $this->printFeatureClass($locationSource, FeatureClass::V, $isoLanguage, 'forest, heath,...');

        if (is_null($this->timeStart)) {
            return;
        }

        $timeExecution = microtime(true) - $this->timeStart;

        $this->output->writeln('');
        $this->output->writeln(sprintf('Execution time: %dms', $timeExecution * 1000));
        $this->output->writeln('');
    }

    /**
     * Prints the header.
     *
     * @param string $header
     * @return void
     */
    private function printHeader(string $header): void
    {
        $this->output->writeln($header);
        $this->output->writeln('');
    }

    /**
     * Prints a separator.
     *
     * @param string $separator
     * @return void
     */
    private function printSeparator(string $separator = '='): void
    {
        $this->output->writeln(str_repeat($separator, self::SEPARATOR_LENGTH));
    }

    /**
     * Prints the place information.
     *
     * @param Location $locationSource
     * @param string $isoLanguage
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    protected function printPlace(Location $locationSource, string $isoLanguage): void
    {
        $this->printSeparator();
        $this->printHeader('Location reference');
        $this->printCaption();
        $this->printLocation($locationSource, 'district', $isoLanguage);
        $this->printSeparator();
        $this->output->writeln('');

        $this->printSeparator();
        $this->printHeader('Location path');
        $this->printCaption();

        $locationContainer = $this->locationContainer;

        if (is_null($locationContainer)) {
            return;
        }

        if ($locationContainer->hasDistrict()) {
            $this->printLocation($locationContainer->getDistrict(), 'district', $isoLanguage);
        }

        if ($locationContainer->hasBorough()) {
            $this->printLocation($locationContainer->getBorough(), 'borough', $isoLanguage);
        }

        if ($locationContainer->hasCity()) {
            $this->printLocation($locationContainer->getCity(), 'city', $isoLanguage);
        }

        if ($locationContainer->hasState()) {
            $this->printLocation($locationContainer->getState(),'state', $isoLanguage);
        }

        if ($locationContainer->hasCountry()) {
            $this->printLocation($locationContainer->getCountry(), 'country', $isoLanguage);
        }
        $this->printSeparator();
    }

    /**
     * Prints some debugging information.
     *
     * @param Location $locationSource
     * @param string $featureClass
     * @param string $isoLanguage
     * @param string $description
     * @return void
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function printFeatureClass(
        Location $locationSource,
        string $featureClass,
        string $isoLanguage,
        string $description
    ): void
    {
        $featureCodes = $this->locationServiceConfig->getFeatureCodesByFeatureClass($featureClass);

        $adminCodesGeneral = $this->locationServiceConfig->getAdminCodesGeneral($locationSource);
        $locationCountry = $locationSource->getCountry();

        $locations = [];
        foreach ($featureCodes as $featureCode) {
            $limit = $this->locationServiceConfig->getLimit(
                featureClass: $featureClass,
                featureCode: $featureCode,
                default: $this->debugLimit
            );
            $country = $this->locationServiceConfig->isUseLocationCountry($featureClass, $featureCode) ? $locationCountry : null;
            $adminCodes = $this->locationServiceConfig->isUseAdminCodesGeneral($featureClass, $featureCode) ? $adminCodesGeneral : null;
            $distanceMeter = $this->locationServiceConfig->getDistance($featureClass, $featureCode);

            $limit = max($limit, $this->debugLimit);

            $featureCodeLocations = $this->locationRepository->findLocationsByCoordinate(
                coordinate: $this->coordinate,
                distanceMeter: $distanceMeter,
                featureClasses: $featureClass,
                featureCodes: $featureCode,
                limit: $limit,
                country: $country,
                adminCodes: $adminCodes,
            );

            foreach ($featureCodeLocations as $featureCodeLocation) {
                $locations[] = [
                    'location' => $featureCodeLocation,
                    'distance' => $this->coordinate->getDistance($featureCodeLocation->getCoordinateIxnode())
                ];
            }
        }

        $limit = $this->locationServiceConfig->getLimit(
            featureClass: $featureClass,
            default: $this->debugLimit
        );
        $country = $this->locationServiceConfig->isUseLocationCountry($featureClass) ? $locationCountry : null;
        $adminCodes = $this->locationServiceConfig->isUseAdminCodesGeneral($featureClass) ? $adminCodesGeneral : null;
        $distanceMeter = $this->locationServiceConfig->getDistance($featureClass);

        $limit = max($limit, $this->debugLimit);

        $adminCodesCombined = !is_null($adminCodes) ? array_map(fn($key, $value) => "$key:$value", array_keys($adminCodes), $adminCodes) : null;

        $this->output->writeln('');
        $this->printSeparator();
        $query = sprintf(
            'Query: LOC=%s; FCl=%s; FCo=%s; DST=%s; LIM=%d; CNTRY=%s; ACo=%s',
            sprintf('%f,%f', $this->coordinate->getLatitude(), $this->coordinate->getLongitude()),
            sprintf('%s:%s', $featureClass, str_replace(', ', ',', $description)),
            count($featureCodes) <= self::LIMIT_ARRAY ? implode(',', $featureCodes) : implode(',', array_slice($featureCodes, 0, self::LIMIT_ARRAY)).',...',
            !is_null($distanceMeter) ? sprintf('%sm', $distanceMeter) : 'NULL',
            $limit,
            !is_null($country) ? $country->getCode() : 'NULL',
            !is_null($adminCodesCombined) ? implode(',', $adminCodesCombined) : 'NULL'
        );
        $this->output->writeln($query);
        $this->output->writeln('');

        if (count($locations) <= 0) {
            $this->output->writeln('No locations found.');
            $this->printSeparator();
            return;
        }

        /* Sort by distance */
        usort($locations, fn($item1, $item2) => $item1['distance'] <=> $item2['distance']);

        $this->printCaption();
        foreach ($locations as $location) {
            $location = $location['location'];

            $this->printLocation($location, null, $isoLanguage);
        }
        $this->printSeparator('-');
        $this->output->writeln(sprintf('(%d rows)', count($locations)));
        $this->printSeparator();
    }

    /**
     * Prints the caption.
     *
     * @return void
     */
    protected function printCaption(): void
    {
        $message = sprintf(
            self::DEBUG_CAPTION,
            'Geoname',
            'FCo',
            'Distance',
            'CD',
            'Inhabitents',
            'Admin 1',
            'Admin 2',
            'Admin 3',
            'Admin 4',
            'Location',
            'Name'
        );

        $this->printSeparator('-');
        $this->output->writeln($message);
        $this->printSeparator('-');
    }

    /**
     * Prints a location to screen.
     *
     * @param Location|null $location
     * @param string|null $description
     * @param string $isoLanguage
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function printLocation(Location|null $location, string|null $description = null, string $isoLanguage = 'en'): void
    {
        $geoNameId = $location?->getGeonameId() ?: self::TEXT_NOT_AVAILABLE;
        $distanceKm = $location ? $this->coordinate->getDistance($location->getCoordinateIxnode()) / 1000 : 0;
        $direction = $location ? $this->coordinate->getDirection($location->getCoordinateIxnode()) : 0;
        $featureCode = $location?->getFeatureCode()?->getCode() ?: self::TEXT_NOT_AVAILABLE;
        $distance = number_format($distanceKm, 3, ',', '.').' km';
        $population = number_format((int) $location?->getPopulationCompiled() ?: 0, 0, ',', '.');
        $adminCode1 = $location?->getAdminCode()?->getAdmin1Code() ?: self::TEXT_NOT_AVAILABLE;
        $adminCode2 = $location?->getAdminCode()?->getAdmin2Code() ?: self::TEXT_NOT_AVAILABLE;
        $adminCode3 = $location?->getAdminCode()?->getAdmin3Code() ?: self::TEXT_NOT_AVAILABLE;
        $adminCode4 = $location?->getAdminCode()?->getAdmin4Code() ?: self::TEXT_NOT_AVAILABLE;
        $position = $location?->getPosition() ?: self::TEXT_NOT_AVAILABLE;
        $name = match (true) {
            !is_null($description) => sprintf('%s (%s)', $this->getNameByIsoLanguage($location, $isoLanguage), $description),
            default => $this->getNameByIsoLanguage($location, $isoLanguage),
        };

        $this->output->writeln(sprintf(
            self::DEBUG_CONTENT,
            $geoNameId,
            $featureCode,
            $distance,
            $direction,
            $population,
            $adminCode1,
            $adminCode2,
            $adminCode3,
            $adminCode4,
            $position,
            $name
        ));
    }

    /**
     * Debugs a given location entity.
     *
     * @param LocationEntity $location
     * @return never
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    #[NoReturn]
    public function debugLocation(LocationEntity $location): never
    {
        $distanceKm = $this->coordinate->getDistance($location->getCoordinateIxnode()) / 1000;

        print PHP_EOL;
        print sprintf('Name           | Value'.PHP_EOL);
        print sprintf('---------------+-------------------------------'.PHP_EOL);
        print sprintf('Name           | %s'.PHP_EOL, $location->getName());
        print sprintf('Distance       | %.2fkm'.PHP_EOL, $distanceKm);
        print sprintf('Feature Class  | %s'.PHP_EOL, $location->getFeatureClass()?->getClass() ?: 'n/a');
        print sprintf('Feature Code   | %s'.PHP_EOL, $location->getFeatureCode()?->getCode() ?: 'n/a');
        print sprintf('Admin 1        | %s'.PHP_EOL, $location->getAdminCode()?->getAdmin1Code() ?: 'n/a');
        print sprintf('Admin 2        | %s'.PHP_EOL, $location->getAdminCode()?->getAdmin2Code() ?: 'n/a');
        print sprintf('Admin 3        | %s'.PHP_EOL, $location->getAdminCode()?->getAdmin3Code() ?: 'n/a');
        print sprintf('Admin 4        | %s'.PHP_EOL, $location->getAdminCode()?->getAdmin4Code() ?: 'n/a');
        print PHP_EOL;
        exit();
    }
}
