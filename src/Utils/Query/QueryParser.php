<?php

/*
* This file is part of the ixnode/php-api-version-bundle project.
*
* (c) Björn Hempel <https://www.hempel.li/>
*
* For the full copyright and license information, please view the LICENSE.md
* file that was distributed with this source code.
*/

declare(strict_types=1);

namespace App\Utils\Query;

use App\Constants\Key\KeyArray;
use App\Tests\Unit\Utils\Query\ParserTest;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;
use LogicException;

/**
 * Class QueryParser
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-06)
 * @since 0.1.0 (2024-01-06) First version.
 * @link ParserTest
 */
class QueryParser
{
    final public const TYPE_SEARCH_GEONAME_ID = 'search_geoname_id';

    final public const TYPE_SEARCH_COORDINATE = 'search_coordinate';

    final public const TYPE_SEARCH_LIST_GENERAL = 'search_list_general';

    final public const TYPE_SEARCH_LIST_WITH_FEATURES = 'search_list_with_features';

    private const SEPARATOR_FEATURE_CODES = '|';

    private const LENGTH_FEATURE_CLASS = 1;

    private const LENGTH_FEATURE_CODE = 5;

    private const EREG_WRAPPER_SINGLE = '^%s$';

    private const EREG_WRAPPER_COORDINATE = '^%s *[,/| ]+ *%s$';

    private const EREG_WRAPPER_COORDINATE_WITH_FEATURES = '^%s[ :] *%s *[,/| ]+ *%s$';

    private const FORMAT_ID = '([0-9]+)';

    private const FORMAT_FEATURES_SINGLE = '(?:[A-Z]{1,3}[A-Z0-9]{1,2}|[AHLPRSTUV])';

    private const FORMAT_FEATURES = '('.self::FORMAT_FEATURES_SINGLE.'(?:\\'.self::SEPARATOR_FEATURE_CODES.self::FORMAT_FEATURES_SINGLE.')*)';

    private const FORMAT_DECIMAL = '([-+]?[0-9]+\.[0-9]+)°?';

    private const FORMAT_DMS = '[0-9]+°[0-9]+′[0-9]+\.[0-9]+″';

    private const FORMAT_DMS_LATITUDE = '('.self::FORMAT_DMS.'[NS])';

    private const FORMAT_DMS_LONGITUDE = '('.self::FORMAT_DMS.'[EW])';

    private const FORMAT_LATITUDES = [self::FORMAT_DECIMAL, self::FORMAT_DMS_LATITUDE];

    private const FORMAT_LONGITUDES = [self::FORMAT_DECIMAL, self::FORMAT_DMS_LONGITUDE];

    private readonly string $query;

    private string $type;

    /** @var array{
     *      type: string,
     *      geoname-id: int|null,
     *      latitude: float|null,
     *      longitude: float|null,
     *      feature-classes: string[]|null,
     *      feature-codes: string[]|null,
     *      search: string|null,
     *  } $data */
    private array $data;

    /** @var string[] $matches */
    private array $matches = [];

    /**
     * @param string|int $query
     */
    public function __construct(string|int $query)
    {
        $this->query = trim((string) $query);
    }

    /**
     * Returns the query type (cached).
     *
     * @return string
     */
    public function getType(): string
    {
        if (isset($this->type)) {
            return $this->type;
        }

        $this->type = $this->doGetType();

        return $this->type;
    }

    /**
     * Returns if the given type matches with the query.
     *
     * @param string $type
     * @return bool
     */
    public function isType(string $type): bool
    {
        return $this->getType() === $type;
    }

    /**
     * Returns the query data (cached).
     *
     * @return array{
     *      type: string,
     *      geoname-id: int|null,
     *      latitude: float|null,
     *      longitude: float|null,
     *      feature-classes: string[]|null,
     *      feature-codes: string[]|null,
     *      search: string|null,
     *  }
     *
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getData(): array
    {
        if (isset($this->data)) {
            return $this->data;
        }

        $this->data = $this->doGetData();

        return $this->data;
    }

    /**
     * Returns the geoname-id.
     *
     * @return int|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getGeonameId(): int|null
    {
        $data = $this->getData();

        return $data[KeyArray::GEONAME_ID];
    }

    /**
     * Returns the latitude.
     *
     * @return float|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getLatitude(): float|null
    {
        $data = $this->getData();

        return $data[KeyArray::LATITUDE];
    }

    /**
     * Returns the longitude.
     *
     * @return float|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getLongitude(): float|null
    {
        $data = $this->getData();

        return $data[KeyArray::LONGITUDE];
    }

    /**
     * Returns the feature classes.
     *
     * @return string[]|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getFeatureClasses(): array|null
    {
        $data = $this->getData();

        return $data[KeyArray::FEATURE_CLASSES];
    }

    /**
     * Returns the feature codes.
     *
     * @return string[]|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getFeatureCodes(): array|null
    {
        $data = $this->getData();

        return $data[KeyArray::FEATURE_CODES];
    }

    /**
     * Returns the search string.
     *
     * @return string|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getSearch(): string|null
    {
        $data = $this->getData();

        return $data[KeyArray::SEARCH];
    }

    /**
     * Returns a Coordinate class from given data.
     *
     * @return Coordinate|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getCoordinate(): Coordinate|null
    {
        $data = $this->getData();

        $latitude = $data[KeyArray::LATITUDE];
        $longitude = $data[KeyArray::LONGITUDE];

        if (is_null($latitude) || is_null($longitude)) {
            return null;
        }

        return new Coordinate($latitude, $longitude);
    }

    /**
     * Returns the data container according the given parameter.
     *
     * @param string $type
     * @param int|null $geonameId
     * @param float|null $latitude
     * @param float|null $longitude
     * @param string[]|null $featureClasses
     * @param string[]|null $featureCodes
     * @param string|null $search
     * @return array{
     *     type: string,
     *     geoname-id: int|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     feature-classes: string[]|null,
     *     feature-codes: string[]|null,
     *     search: string|null,
     * }
     */
    public static function getDataContainer(
        string $type,
        int|null $geonameId = null,
        float|null $latitude = null,
        float|null $longitude = null,
        array|null $featureClasses = null,
        array|null $featureCodes = null,
        string|null $search = null
    ): array
    {
        if (!is_null($latitude) && is_null($longitude)) {
            throw new LogicException('If latitude is given, longitude must be given as well.');
        }
        if (!is_null($longitude) && is_null($latitude)) {
            throw new LogicException('If longitude is given, latitude must be given as well.');
        }

        return [
            KeyArray::TYPE => $type,
            KeyArray::GEONAME_ID => $geonameId,
            KeyArray::LATITUDE => $latitude,
            KeyArray::LONGITUDE => $longitude,
            KeyArray::FEATURE_CLASSES => $featureClasses,
            KeyArray::FEATURE_CODES => $featureCodes,
            KeyArray::SEARCH => $search,
        ];
    }

    /**
     * Returns the query type.
     *
     * @return string
     */
    private function doGetType(): string
    {
        /* Just an id was given -> Use the id to query a direct geoname id:
         * - 12345678
         * - 52454235
         */
        if (mb_ereg(sprintf(self::EREG_WRAPPER_SINGLE, self::FORMAT_ID), $this->query, $this->matches)) {
            return self::TYPE_SEARCH_GEONAME_ID;
        }

        /* A coordinate was given -> Use the given coordinate to query according given coordinate:
         * - 52.524889,13.3692797
         * - 28.137008, -15.438614
         * - 51.05811°,13.74133°
         * - 52°31′12.108″N,13°24′17.604″E
         * - 52°31′12.108″N, 13°24′17.604″E
         * - 52°31′12.108″N,13.3692797
         * - 52°31′12.108″N, -15.438614
         * - 52.524889,13°24′17.604″E
         * - 28.137008, 13°24′17.604″E
         * - etc.
         */
        foreach (self::FORMAT_LATITUDES as $formatLatitude) {
            foreach (self::FORMAT_LONGITUDES as $formatLongitude) {
                if (mb_ereg(sprintf(self::EREG_WRAPPER_COORDINATE, $formatLatitude, $formatLongitude), $this->query, $this->matches)) {
                    return self::TYPE_SEARCH_COORDINATE;
                }
            }
        }

        /* A coordinate with feature class/code was given -> Use search list query with features:
         * - AIRP 52.524889,13.3692797
         * - AIRP 28.137008, -15.438614
         * - AIRP 51.05811°,13.74133°
         * - AIRP 52°31′12.108″N,13°24′17.604″E
         * - AIRP 52°31′12.108″N, 13°24′17.604″E
         * - AIRP 52°31′12.108″N,13.3692797
         * - AIRP 52°31′12.108″N, -15.438614
         * - AIRP 52.524889,13°24′17.604″E
         * - AIRP 28.137008, 13°24′17.604″E
         * - AIRP|AIRT 28.137008, 13°24′17.604″E
         * - S 28.137008, 13°24′17.604″E
         * - S|AIRP|AIRT 28.137008, 13°24′17.604″E
         * - etc.
         */
        foreach (self::FORMAT_LATITUDES as $formatLatitude) {
            foreach (self::FORMAT_LONGITUDES as $formatLongitude) {
                if (mb_ereg(sprintf(self::EREG_WRAPPER_COORDINATE_WITH_FEATURES, self::FORMAT_FEATURES, $formatLatitude, $formatLongitude), $this->query, $this->matches)) {
                    return self::TYPE_SEARCH_LIST_WITH_FEATURES;
                }
            }
        }

        /* Use the query as a search list query:
         * - all the rest
         */
        $this->matches = [$this->query, $this->query];
        return self::TYPE_SEARCH_LIST_GENERAL;
    }

    /**
     * Returns the query data.
     *
     * @return array{
     *      type: string,
     *      geoname-id: int|null,
     *      latitude: float|null,
     *      longitude: float|null,
     *      feature-classes: string[]|null,
     *      feature-codes: string[]|null,
     *      search: string|null,
     *  }
     *
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    private function doGetData(): array
    {
        $type = $this->getType();

        return match ($type) {
            self::TYPE_SEARCH_GEONAME_ID => $this->getDataContainerParsed(
                $type,
                geonameId: (int) $this->matches[1]
            ),
            self::TYPE_SEARCH_COORDINATE => $this->getDataContainerParsed(
                $type, latitude: $this->matches[1],
                longitude: $this->matches[2]
            ),
            self::TYPE_SEARCH_LIST_WITH_FEATURES => $this->getDataContainerParsed(
                $type,
                latitude: $this->matches[2],
                longitude: $this->matches[3],
                features: $this->matches[1]
            ),
            self::TYPE_SEARCH_LIST_GENERAL => $this->getDataContainerParsed(
                $type,
                search: $this->matches[1]
            ),
            default => throw new LogicException(sprintf('Unknown query type "%s".', $type)),
        };
    }

    /**
     * Returns the data container according the given parameter.
     *
     * @param string $type
     * @param int|null $geonameId
     * @param string|float|null $latitude
     * @param string|float|null $longitude
     * @param string[]|string|null $features
     * @param string|null $search
     * @return array{
     *     type: string,
     *     geoname-id: int|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     feature-classes: string[]|null,
     *     feature-codes: string[]|null,
     *     search: string|null,
     * }
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    private function getDataContainerParsed(
        string $type,
        int|null $geonameId = null,
        string|float|null $latitude = null,
        string|float|null $longitude = null,
        array|string|null $features = null,
        string|null $search = null
    ): array
    {
        if (!is_null($latitude) && is_null($longitude)) {
            throw new LogicException('If latitude is given, longitude must be given as well.');
        }
        if (!is_null($longitude) && is_null($latitude)) {
            throw new LogicException('If longitude is given, latitude must be given as well.');
        }

        if (is_string($latitude) || is_string($longitude)) {
            $coordinate = new Coordinate($latitude, $longitude);

            $latitude = $coordinate->getLatitudeDecimal();
            $longitude = $coordinate->getLongitudeDecimal();
        }

        [
            KeyArray::FEATURE_CLASSES => $featureClasses,
            KeyArray::FEATURE_CODES => $featureCodes
        ] = $this->extractFeatures($features);

        return self::getDataContainer(
            $type,
            geonameId: $geonameId,
            latitude: $latitude,
            longitude: $longitude,
            featureClasses: $featureClasses,
            featureCodes: $featureCodes,
            search: $search
        );
    }

    /**
     * Extract features (featureClasses and featureCodes) from given features.
     *
     * @param string[]|string|null $features
     * @return array{feature-classes: string[]|null, feature-codes: string[]|null}
     */
    private function extractFeatures(array|string|null $features = null): array
    {
        if (is_null($features)) {
            return [
                KeyArray::FEATURE_CLASSES => null,
                KeyArray::FEATURE_CODES => null
            ];
        }

        if (is_string($features)) {
            $features = explode(self::SEPARATOR_FEATURE_CODES, $features);
        }

        $featureClasses = [];
        $featureCodes = [];

        foreach ($features as $feature) {
            match (true) {
                mb_strlen($feature) === self::LENGTH_FEATURE_CLASS => $featureClasses[] = $feature,
                mb_strlen($feature) > self::LENGTH_FEATURE_CLASS && mb_strlen($feature) <= self::LENGTH_FEATURE_CODE => $featureCodes[] = $feature,
                default => throw new LogicException(sprintf('Unsupported feature code length given "%s".', $feature)),
            };
        }

        if (count($featureClasses) <= 0) {
            $featureClasses = null;
        }

        if (count($featureCodes) <= 0) {
            $featureCodes = null;
        }

        return [
            KeyArray::FEATURE_CLASSES => $featureClasses,
            KeyArray::FEATURE_CODES => $featureCodes,
        ];
    }
}
