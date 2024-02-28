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

namespace App\Command\Property\Crawler;

use App\Constants\Key\KeyArray;
use App\Constants\Property\Airport\IataUrl;
use App\Entity\Location;
use App\Entity\Property;
use App\Entity\Source;
use App\Repository\AlternateNameRepository;
use App\Repository\SourceRepository;
use App\Utils\Constant\IgnoreBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use Ixnode\PhpWebCrawler\Converter\Collection\Base\ConverterArray;
use Ixnode\PhpWebCrawler\Converter\Collection\Chunk;
use Ixnode\PhpWebCrawler\Converter\Collection\First;
use Ixnode\PhpWebCrawler\Converter\Collection\HighestNumber;
use Ixnode\PhpWebCrawler\Converter\Collection\Implode;
use Ixnode\PhpWebCrawler\Converter\Collection\RemoveEmpty;
use Ixnode\PhpWebCrawler\Converter\Scalar\Base\Converter;
use Ixnode\PhpWebCrawler\Converter\Scalar\Boolean;
use Ixnode\PhpWebCrawler\Converter\Scalar\Combine;
use Ixnode\PhpWebCrawler\Converter\Scalar\Number;
use Ixnode\PhpWebCrawler\Converter\Scalar\PregMatch;
use Ixnode\PhpWebCrawler\Converter\Scalar\PregReplace;
use Ixnode\PhpWebCrawler\Converter\Scalar\Replace;
use Ixnode\PhpWebCrawler\Converter\Scalar\Sprintf;
use Ixnode\PhpWebCrawler\Converter\Scalar\ToLower;
use Ixnode\PhpWebCrawler\Converter\Scalar\Trim;
use Ixnode\PhpWebCrawler\Output\Field;
use Ixnode\PhpWebCrawler\Output\Group;
use Ixnode\PhpWebCrawler\Source\Url;
use Ixnode\PhpWebCrawler\Source\XpathSections;
use Ixnode\PhpWebCrawler\Value\LastUrl;
use Ixnode\PhpWebCrawler\Value\Text;
use Ixnode\PhpWebCrawler\Value\XpathTextNode;
use JsonException;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WikipediaAirportCrawlerCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-24)
 * @since 0.1.0 (2024-02-24) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @example bin/console crawler:wikipedia:airport MAD
 *
 * ====================
 * Search for iata code "MAD"
 * ====================
 * Hits:   1
 * Type:   1 found via list search from "https://en.wikipedia.org/wiki/MAD"
 * --------------------
 * IATA:   MAD
 * Name:   Adolfo Suárez Madrid–Barajas Airport
 * Link:   https://en.wikipedia.org/wiki/Adolfo_Su%C3%A1rez_Madrid%E2%80%93Barajas_Airport
 * --------------------
 * Data:
 * {
 *     "data": {
 *         "airport": {
 *             "iata": "MAD",
 *             "icao": "LEMD",
 *             "wmo": "08221",
 *             "passengers": 50633652,
 *             "movements": 351906,
 *             "cargo": 566372618,
 *             "website": "http://www.aena.es/en/madrid-barajas-airport/index.html",
 *             "operator": "Aena, Bombardier Transportation",
 *             "opened": "1931-04-22",
 *             "type": "public",
 *             "statistics-year": "2022",
 *             "runways": [
 *                 {
 *                     "direction": "14R/32L",
 *                     "length": 4100,
 *                     "surface": "asphalt"
 *                 },
 *                 {
 *                     "direction": "18L/36R",
 *                     "length": 3500,
 *                     "surface": "asphalt"
 *                 },
 *                 {
 *                     "direction": "14L/32R",
 *                     "length": 3500,
 *                     "surface": "asphalt"
 *                 },
 *                 {
 *                     "direction": "18R/36L",
 *                     "length": 4350,
 *                     "surface": "asphalt/concrete"
 *                 }
 *             ]
 *         },
 *         "general": {
 *             "elevation": 610
 *         }
 *     },
 *     "source": {
 *         "type": "wiki",
 *         "link": "https://en.wikipedia.org/wiki/Adolfo_Su%C3%A1rez_Madrid%E2%80%93Barajas_Airport"
 *     }
 * }
 * ====================
 * IATA confirmed.
 * ====================
 *
 * @example
 *
 * SELECT p.id, p.location_id, p.property_name, p.property_value, l.name, a.alternate_name
 * FROM property p
 * JOIN location l ON p.location_id = l.id
 * JOIN alternate_name a ON a.location_id = l.id
 * WHERE p.property_name = 'passengers' AND a.iso_language = 'iata'
 * ORDER BY property_value::INTEGER DESC
 *
 */
class WikipediaAirportCrawlerCommand extends Command
{
    protected static $defaultName = 'crawler:wikipedia:airport';

    private const WIKIPEDIA_LIST_SEARCHES = [
        'may refer to:',
        'can refer to:',
        'may also refer to:',
        'It refers to:',
        'can also refer to:',
        'may stand for:',
        'are used for:',
    ];

    private const WIKIPEDIA_AIRPORT_SEARCHES = [
        'airport',
        'iata',
        'airfield',
        'airbase',
        'air base',
    ];

    private const ZERO_RESULT = 0;

    private const NUMBER_1 = 1;

    private const SEPARATOR_COUNT = 20;

    private const RUNWAY_CHUNK_SIZE = 3;

    private const RUNWAY_SEPARATOR = ', ';

    private const IGNORE_CLASS_PATH = ['Constants', 'Property', 'Airport', 'IataIgnore'];

    private const COMMAND_ALREADY_EXISTS_OR_IGNORED = 3;

    private const COMMAND_NOT_FOUND_LIST = 4;

    private const COMMAND_NOT_FOUND_LIST_DISAMBIGUATION = 5;

    private const COMMAND_NOT_FOUND_PAGE = 6;

    private const COMMAND_NOT_CONFIRMED = 7;

    private const DOMAIN_WIKIPEDIA = 'https://en.wikipedia.org';

    private const DOMAIN_WIKIPEDIA_SEARCH = 'https://en.wikipedia.org/wiki/%s';

    private const DOMAIN_WIKIPEDIA_SEARCH_DISAMBIGUATION = 'https://en.wikipedia.org/wiki/%s_(disambiguation)';

    private int $propertiesAdded = 0;

    private bool $ignoreExistingProperties = true;

    private OutputInterface $output;

    private InputInterface $input;

    /**
     * @param EntityManagerInterface $entityManager
     * @param AlternateNameRepository $alternateNameRepository
     * @param SourceRepository $sourceRepository
     * @param IgnoreBuilder $ignoreBuilder
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected AlternateNameRepository $alternateNameRepository,
        protected SourceRepository $sourceRepository,
        protected IgnoreBuilder $ignoreBuilder
    )
    {
        parent::__construct();
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setName(strval(self::$defaultName))
            ->setDescription('Crawls wikipedia pages for airport properties.')
            ->setDefinition([
                new InputArgument(KeyArray::IATA, InputArgument::OPTIONAL, 'The iata code to be parsed.', null),
                new InputOption(KeyArray::MAX_RESULTS, null, InputOption::VALUE_OPTIONAL, 'Maximum number of results to return', null),
                new InputOption(KeyArray::DEBUG_PAGE, null, InputOption::VALUE_NONE, 'Enable debug mode (page view)'),
                new InputOption(KeyArray::DEBUG_SEARCH, null, InputOption::VALUE_NONE, 'Enable debug mode (search view)'),
                new InputOption(KeyArray::FORCE, null, InputOption::VALUE_NONE, 'Forces already imported iata codes'),
            ])
            ->setHelp(
                <<<'EOT'

The <info>crawler:wikipedia:airport</info> crawls wikipedia pages for airport properties.

EOT
            );
    }

    /**
     * @return bool
     */
    public function isIgnoreExistingProperties(): bool
    {
        return $this->ignoreExistingProperties;
    }

    /**
     * @param bool $ignoreExistingProperties
     * @return self
     */
    public function setIgnoreExistingProperties(bool $ignoreExistingProperties): self
    {
        $this->ignoreExistingProperties = $ignoreExistingProperties;

        return $this;
    }

    /**
     * Returns the link or name from parsed response.
     *
     * @param Json $parsed
     * @param string $listKeyName
     * @param string $detailKeyName
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getProperty(Json $parsed, string $listKeyName, string $detailKeyName): string
    {
        if (!$parsed->getKeyBoolean('is-list-page')) {
            return $parsed->getKeyString($detailKeyName);
        }

        if (!$parsed->hasKey('hits') || count($parsed->getKeyArray('hits')) <= self::ZERO_RESULT) {
            return $parsed->getKeyString($detailKeyName);
        }

        $property = $parsed->getKey(['hits', 0, $listKeyName]);

        $property = match (true) {
            is_string($property) => $property,
            is_array($property) && count($property) > self::ZERO_RESULT => $property[0],
            is_null($property) => '',
            default => throw new LogicException(sprintf('Invalid link type given: %s', gettype($property))),
        };

        if (!is_string($property)) {
            throw new LogicException(sprintf('Invalid link type given: %s', gettype($property)));
        }

        return $property;
    }

    /**
     * Returns the property name.
     *
     * @param Json $parsed
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getPropertyName(Json $parsed): string
    {
        return $this->getProperty($parsed, 'name', 'title');
    }

    /**
     * Returns the property link.
     *
     * @param Json $parsed
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getPropertyLink(Json $parsed): string
    {
        return $this->getProperty($parsed, 'link', 'last-url');
    }

    /**
     * Returns the crawler query Field class.
     *
     * @param string $key
     * @param string|string[] $search
     * @param string|null $searchNot
     * @param Converter[]|ConverterArray[] $converters
     * @param string $subElements
     * @return Field
     */
    private function getField(string $key, string|array $search, string $searchNot = null, array $converters = [], string $subElements = ''): Field
    {
        if (is_string($search)) {
            $search = [$search];
        }

        foreach ($search as &$value) {
            $value = sprintf(
                '(contains(translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "%s")%s)',
                strtolower($value),
                !is_null($searchNot) ? sprintf(' and not(contains(translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "%s"))', strtolower($searchNot)) : ''
            );
        }

        $search = implode(' or ', $search);

        return new Field($key, new XpathTextNode(
            sprintf('/html/body//tr[th[contains(@class, "infobox-label") and (%s)]]/td[contains(@class, "infobox-data")]%s', $search, $subElements),
            ...$converters
        ));
    }

    /**
     * Returns the airport Field classes.
     *
     * @param string $link
     * @return Field[]|Group[]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getFieldsAirport(string $link): array
    {
        $xpathRunwaysTable = '/html/body//tr/th[contains(@class, "infobox-header") and contains(., "Runways")]/ancestor::tr/following-sibling::tr[1]/td[contains(@class, "infobox-full-data")]/table%s';

        return [
            new Group(
                'data',
                new Group(
                    'airport',
                    new Field('iata', new XpathTextNode(
                        '/html/body//li/span/a[contains(text(), "IATA")]/../span[@class="nickname"]',
                        new First()
                    )),
                    new Field('icao', new XpathTextNode(
                        '/html/body//li/span/a[contains(text(), "ICAO")]/../span[@class="nickname"]',
                        new First()
                    )),
                    new Field('wmo', new XpathTextNode(
                        '/html/body//li/span/a[contains(text(), "WMO")]/../span[@class="nickname"]',
                        new First(),
                        new Number()
                    )),
                    $this->getField(
                        key: 'passengers',
                        search: ['passengers', 'passenger volume'],
                        searchNot: 'change',
                        converters: [new First(), new Number(['.', ','], ''), new HighestNumber()],
                        subElements: '//text()',
                    ),
                    $this->getField(
                        key: 'movements',
                        search: ['aircraft movements', 'movements', 'aircraft operations'],
                        searchNot: 'change',
                        converters: [new First(), new Number(['.', ','], ''), new HighestNumber()],
                        subElements: '//text()',
                    ),
                    $this->getField(
                        key: 'cargo',
                        search: ['cargo'],
                        searchNot: 'change',
                        converters: [new First(), new Number(['.', ','], ''), new HighestNumber()],
                        subElements: '//text()',
                    ),
                    $this->getField(
                        key: 'website',
                        search: 'website',
                        converters: [new Trim(), new First()],
                        subElements: '//a/@href'
                    ),
                    $this->getField(
                        key: 'operator-complex',
                        search: 'operator',
                        converters: [new Trim(), new RemoveEmpty(), new Implode(), new Replace(', (', ' (')],
                        subElements: '//*[not(./text()/parent::style)]/text()'
                    ),
                    $this->getField(
                        key: 'operator',
                        search: 'operator',
                        converters: [new Trim(), new RemoveEmpty(), new Implode()],
                        subElements: '/text()'
                    ),
                    $this->getField(
                        key: 'opened',
                        search: 'opened',
                        converters: [
                            new Trim(),
                            new PregMatch('/(\d{4}(?:-\d{2}-\d{2})?)/', 1),
                            new First()
                        ]
                    ),
                    $this->getField(
                        key: 'type',
                        search: 'airport type',
                        converters: [
                            new Trim(),
                            new ToLower(),
                            new Replace('/', ', ')
                        ]
                    ),
                    new Field('statistics-year', new XpathTextNode(
                        '/html/body//tr/th[contains(@class, "infobox-header") and contains(., "Statistics")]',
                        new Trim(),
                        new PregMatch('~Statistics \(([0-9]+)(?:\[[0-9]+\])?\)~', 1)
                    )),
                    new Field('runways', new XpathTextNode(
                        sprintf($xpathRunwaysTable, '//tr[position() > 2]/td[position() = 1 or position() = 2 or position() = 4]'),
                        new Trim(),
                        new Chunk(
                            chunkSize: self::RUNWAY_CHUNK_SIZE,
                            separator: self::RUNWAY_SEPARATOR,
                            arrayKeys: ['direction', 'length', 'surface'],
                            scalarConverters: [
                                null,
                                new Number([',', '.'], ''),
                                new Combine(
                                    new ToLower(),
                                    new PregReplace('~[ ]*[/,]+[ ]*~', '/'),
                                )
                            ]
                        )
                    )),
                ),

                new Group(
                    'general',
                    $this->getField(
                        key: 'elevation',
                        search: 'elevation',
                        converters: [new Trim(), new PregMatch('~([0-9]+(?:[,.][0-9]+)?)(?:[\xc2\xa0 ]+)m~', 1), new Number([',', '.'], '')]
                    )
                ),
            ),

            new Group(
                'source',
                new Field('type', new Text('wikipedia')),
                new Field('link', new Text($link))
            )
        ];
    }

    /**
     * Returns the contains query for XPath expressions.
     *
     * @param string|string[] $search
     * @return string
     */
    private function getXpathContains(string|array $search): string
    {
        if (is_string($search)) {
            $search = [$search];
        }

        $search = array_map(fn(string $item) => sprintf('contains(translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "%s")', $item), $search);

        return implode(' or ', $search);
    }

    /**
     * Returns the airport wikipedia page from wikipedia search.
     *
     * @param string $template
     * @param string $iata
     * @return Json|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getPage(string $template, string $iata): Json|null
    {
        $site = match (true) {
            array_key_exists($iata, IataUrl::URL) => IataUrl::URL[$iata],
            default => sprintf($template, $iata),
        };

        $url = new Url(
            $site,
            new Field('title', new XpathTextNode('/html/head/title')),
            new Field('last-url', new LastUrl()),
            new Field('is-list-page', new XpathTextNode(
                sprintf('/html/body//*[@id="mw-content-text"]/div/p[%s]', $this->getXpathContains(self::WIKIPEDIA_LIST_SEARCHES)),
                new Boolean(),
                new First()
            )),
            new Group(
                'hits',
                new XpathSections(
                    sprintf('/html/body//div[@id="mw-content-text"]//ul/li[%s]', $this->getXpathContains(self::WIKIPEDIA_AIRPORT_SEARCHES)),
                    new Field('link', new XpathTextNode('./a[not(contains(@href, "airport_code"))]/@href', new Sprintf(self::DOMAIN_WIKIPEDIA.'%s'))),
                    new Field('name', new XpathTextNode('./a[not(contains(@href, "airport_code"))]/text()')),
                )
            ),
            new Group(
                'hits-deep',
                new XpathSections(
                    '/html/body//div[@id="mw-content-text"]//ul/li/ul/li[contains(translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "airport") or contains(translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "iata")]',
                    new Field('link', new XpathTextNode('./a[not(contains(@href, "airport_code"))]/@href', new Sprintf(self::DOMAIN_WIKIPEDIA.'%s'))),
                    new Field('name', new XpathTextNode('./a[not(contains(@href, "airport_code"))]/text()')),
                )
            ),
            new Group(
                'data',
                ...$this->getFieldsAirport($site)
            ),
        );

        $parsed = $url->parse();

        if ((bool) $this->input->getOption(KeyArray::DEBUG_SEARCH)) {
            $this->printDebug($parsed->getJsonStringFormatted());
            return null;
        }

        if ($parsed->hasKey('hits-deep') && count($parsed->getKeyArray('hits-deep')) > self::ZERO_RESULT) {
            $parsed->addValue('hits', $parsed->getKeyArray('hits-deep'));
        }

        $parsed->deleteKey('hits-deep');

        return match (true) {
            !$parsed->getKeyBoolean('is-list-page') => $parsed,
            $parsed->hasKey('hits') && (count($parsed->getKeyArray('hits')) > self::ZERO_RESULT) => $parsed,
            default => null,
        };
    }

    /**
     * Returns the parsed wikipedia data (not formatted).
     *
     * @param Json $parsed
     * @param string $link
     * @return Json
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function doGetPageData(Json $parsed, string $link): Json
    {
        if (!$parsed->getKeyBoolean('is-list-page')) {
            return $parsed->getKeyJson(['data']);
        }

        $link = $this->getPropertyLink($parsed);

        $url = new Url(
            $link,
            ...$this->getFieldsAirport($link)
        );

        return $url->parse();
    }

    /**
     * Returns the parsed wikipedia data.
     *
     * @param Json $parsed
     * @param string $link
     * @return Json|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getPageData(Json $parsed, string $link): Json|null
    {
        $pageData = $this->doGetPageData($parsed, $link);

        $airportOperatorComplex = $pageData->getKey(['data', 'airport', 'operator-complex']);
        $airportOperator = $pageData->getKey(['data', 'airport', 'operator']);

        if (!is_string($airportOperatorComplex) && !is_null($airportOperatorComplex)) {
            throw new TypeInvalidException(sprintf('Unsupported type for airport-operator-complex: %s', gettype($airportOperatorComplex)));
        }

        if (!is_string($airportOperator) &&!is_null($airportOperator)) {
            throw new TypeInvalidException(sprintf('Unsupported type for airport-operator: %s', gettype($airportOperator)));
        }

        if (is_null($airportOperator) && is_string($airportOperatorComplex)) {
            $airportOperator = $airportOperatorComplex;
        }

        $pageData->addValue(['data', 'airport', 'operator'], $airportOperator);
        $pageData->deleteKey(['data', 'airport', 'operator-complex']);

        if ((bool) $this->input->getOption(KeyArray::DEBUG_PAGE)) {
            $this->printDebug($pageData->getJsonStringFormatted());
            return null;
        }

        return $pageData;
    }

    /**
     * Returns the source entity.
     *
     * @param string $sourceType
     * @param string $sourceLink
     * @return Source
     */
    private function getSourceEntity(string $sourceType, string $sourceLink): Source
    {
        $source = $this->sourceRepository->findOneBy(['sourceType' => $sourceType, 'sourceLink' => $sourceLink]);

        if (is_null($source)) {
            $source = new Source();
            $source->setSourceType($sourceType);
            $source->setSourceLink($sourceLink);
            $this->entityManager->persist($source);
            $this->entityManager->flush();
        }

        return $source;
    }

    /**
     * Returns the property entity.
     *
     * @param Location $location
     * @param Source $source
     * @param string $name
     * @param string|int $value
     * @param string $type
     * @param int|null $number
     * @return Property
     */
    private function getPropertyEntity(
        Location $location,
        Source $source,
        string $name,
        string|int $value,
        string $type,
        int $number = null
    ): Property
    {
        $property = new Property();
        $property->setLocation($location);
        $property->setSource($source);
        $property->setPropertyName($name);
        $property->setPropertyValue((string) $value);
        $property->setPropertyType($type);

        if (!is_null($number)) {
            $property->setPropertyNumber($number);
        }

        return $property;
    }

    /**
     * Adds the given array data to db.
     *
     * @param Location $location
     * @param Source $source
     * @param array<string|int, mixed> $data
     * @param string $type
     * @param int|null $number
     * @return void
     */
    private function doAddProperties(
        Location $location,
        Source $source,
        array $data,
        string $type,
        int|null $number = null
    ): void
    {
        foreach ($data as $key => $value) {
            switch (true) {
                /* Ignore empty values. */
                case is_null($value):
                    break;

                /* Add strings and integer values to db. */
                case is_string($value):
                case is_int($value):
                    $this->entityManager->persist(
                        $this->getPropertyEntity($location, $source, (string) $key, $value, $type, $number)
                    );
                    $this->propertiesAdded++;
                    break;

                /* Recursive iteration for arrays. */
                case is_array($value):
                    foreach ($value as $index => $subData) {
                        if (!is_int($index)) {
                            throw new LogicException(sprintf('Unsupported type for array index: %s', gettype($index)));
                        }

                        $this->doAddProperties($location, $source, $subData, $type, $index);
                    }
                    break;

                default:
                    throw new LogicException(sprintf('Unsupported type for key "%s": %s', $key, gettype($value)));
            }
        }
    }

    /**
     * Adds properties to the given location entity.
     *
     * @param Location $location
     * @param Json $data
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
    private function addProperties(Location $location, Json $data): void
    {
        $source = $this->getSourceEntity(
            $data->getKeyString(['source', 'type']),
            $data->getKeyString(['source', 'link'])
        );

        /* Delete existing properties. */
        $properties = $location->getProperties();
        if (count($properties) > self::ZERO_RESULT) {
            $this->output->write(sprintf('Delete %d properties from location ... ', count($properties)));
            foreach ($properties as $property) {
                $this->entityManager->remove($property);
            }
            $this->entityManager->flush();
            $this->output->writeln('Done.');
        }

        $this->propertiesAdded = 0;

        $dataAirport = $data->getKeyArray([KeyArray::DATA, KeyArray::AIRPORT]);
        $this->doAddProperties($location, $source, $dataAirport, KeyArray::AIRPORT);

        $dataGeneral = $data->getKeyArray([KeyArray::DATA, KeyArray::GENERAL]);
        $this->doAddProperties($location, $source, $dataGeneral, KeyArray::GENERAL);

        $this->entityManager->flush();
        $this->output->writeln(sprintf('Added %d properties to location.', $this->propertiesAdded));
    }

    /**
     * Do the iata job.
     *
     * @param string $iata
     * @return int
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws NonUniqueResultException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function doIata(string $iata): int
    {
        $iata = strtoupper($iata);

        $this->output->writeln('');
        $this->output->writeln(sprintf('Starting iata: %s', $iata));
        $this->output->writeln('');

        $alternateName = $this->alternateNameRepository->findOneByAirportAndIata($iata, $this->isIgnoreExistingProperties());

        /* Only existing airports are allowed. */
        if (is_null($alternateName)) {
            $this->output->writeln(sprintf('<error>No airport location without properties found for %s within db or is ignored (IataIgnore::IGNORE).</error>', $iata));
            return self::COMMAND_ALREADY_EXISTS_OR_IGNORED;
        }

        $location = $alternateName->getLocation();

        if (is_null($location)) {
            throw new LogicException('Location does not exist.');
        }

        $parsed = $this->getPage(self::DOMAIN_WIKIPEDIA_SEARCH, $iata);
        if (is_null($parsed)) {
            $this->output->writeln(sprintf('<error>No airport page found for %s on wikipedia page.</error>', $iata));
            return self::COMMAND_NOT_FOUND_LIST;
        }
        $isListPage = $parsed->getKeyBoolean('is-list-page');

        /* Try disambiguation page if this is not a list page and no iata is found. */
        if (!$isListPage && $parsed->getKey(['data', 'data', 'airport', 'iata']) === null) {
            $parsed = $this->getPage(self::DOMAIN_WIKIPEDIA_SEARCH_DISAMBIGUATION, $iata);
            if (is_null($parsed)) {
                $this->output->writeln(sprintf('<error>No airport page found for %s on wikipedia page.</error>', $iata));
                return self::COMMAND_NOT_FOUND_LIST_DISAMBIGUATION;
            }
            $isListPage = $parsed->getKeyBoolean('is-list-page');
        }

        /* Use the first hit or the title and the last url of the page. */
        $name = $this->getPropertyName($parsed);
        $link = $this->getPropertyLink($parsed);
        $numberHits = $isListPage ? count($parsed->getKeyArray('hits')) : self::NUMBER_1;

        /* Get the airport data. */
        $data = $this->getPageData($parsed, $link);

        if (is_null($data)) {
            $this->output->writeln(sprintf('<error>No airport page found for %s on wikipedia page.</error>', $iata));
            return self::COMMAND_NOT_FOUND_PAGE;
        }

        $airportIataConfirmed = $data->hasKey(['data', 'airport', 'iata']) && $data->getKey(['data', 'airport', 'iata']) === $iata;

        /* Last URL */
        $lastUrl = $parsed->getKey('last-url');
        $lastUrl = is_string($lastUrl) ? $lastUrl  : null;

        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln(sprintf('<info>Search for iata code "%s"</info>', $iata));
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln(sprintf('Hits:        %d', $numberHits));
        $this->output->writeln(sprintf('Type:        %s', $isListPage ? sprintf('%d found via list search from "%s"', $numberHits, $lastUrl) : 'Direct Page'));
        $this->output->writeln(str_repeat('-', self::SEPARATOR_COUNT));
        $this->output->writeln(sprintf('AN ID:       %s', $alternateName->getId()));
        $this->output->writeln(sprintf('Location ID: %s', $location->getId()));
        $this->output->writeln(str_repeat('-', self::SEPARATOR_COUNT));
        $this->output->writeln(sprintf('IATA:        %s', $iata));
        $this->output->writeln(sprintf('Name:        %s', $name));
        $this->output->writeln(sprintf('Link:        %s', $link));
        $this->output->writeln(str_repeat('-', self::SEPARATOR_COUNT));
        $this->output->writeln('Data:');
        $this->output->writeln($data->getJsonStringFormatted());
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln($airportIataConfirmed ? '<info>IATA confirmed.</info>' : '<error>IATA not confirmed.</error>');
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln('');

        if (!$airportIataConfirmed) {
            return self::COMMAND_NOT_CONFIRMED;
        }

        /* Add properties from wikipedia page. */
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln('<info>DB actions.</info>');
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->addProperties($location, $data);
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln('<info>DB actions done.</info>');
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));

        return Command::SUCCESS;
    }

    /**
     * Returns all iata codes.
     *
     * @return string[]
     */
    private function getIatas(): array
    {
        $iata = $this->input->getArgument(KeyArray::IATA);

        if (!is_string($iata) && !is_null($iata)) {
            throw new LogicException('Unsupported type of iata given.');
        }

        if (is_string($iata)) {
            return [$iata];
        }

        $maxResults = $this->input->getOption(KeyArray::MAX_RESULTS);

        $maxResults = match (true) {
            is_null($maxResults), is_int($maxResults) => $maxResults,
            is_string($maxResults) => (int) $maxResults,
            default => throw new LogicException('Unsupported type of max results given.')
        };

        return $this->alternateNameRepository->findIataCodes($maxResults, true);
    }

    /**
     * Prints debug message.
     *
     * @param string $message
     * @return void
     */
    private function printDebug(string $message): void
    {
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln('<info>Debug.</info>');
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln($message);
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln('');
    }

    /**
     * Returns the "ignore" message by given return value.
     *
     * @param int $return
     * @return string|null
     */
    private function getIgnoreMessage(int $return): string|null
    {
        return match ($return) {
            self::COMMAND_ALREADY_EXISTS_OR_IGNORED => null,
            self::COMMAND_NOT_FOUND_LIST => 'Airport not found in Wikipedia search.',
            self::COMMAND_NOT_FOUND_LIST_DISAMBIGUATION => 'Airport not found in Wikipedia disambiguation search.',
            self::COMMAND_NOT_FOUND_PAGE => 'Airport not found in Wikipedia page.',
            self::COMMAND_NOT_CONFIRMED => 'Airport not confirmed on Wikipedia page.',
            default => throw new LogicException(sprintf('Unsupported return code "%s".', $return)),
        };
    }

    /**
     * Execute the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws TypeInvalidException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /* Set Property Airport IataIgnore class. */
        $this->ignoreBuilder->setClassPath(self::IGNORE_CLASS_PATH);

        $this->output = $output;
        $this->input = $input;

        $force = (bool) $this->input->getOption(KeyArray::FORCE);

        if ($force) {
            $this->setIgnoreExistingProperties(false);
        }

        $iatas = $this->getIatas();

        $this->output->writeln('');
        foreach ($iatas as $iata) {
            $return = $this->doIata($iata);

            /* Success returned. */
            if ($return === Command::SUCCESS) {
                continue;
            }

            /* Stop here if no force mode was set. */
            if (!$force) {
                $this->output->writeln('');
                return $return;
            }

            $message = $this->getIgnoreMessage($return);

            if (!is_null($message)) {
                $this->ignoreBuilder->addIgnoreVariable($iata, $message);
            }
        }

        $this->output->writeln('');
        return Command::SUCCESS;
    }
}
