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
use Ixnode\PhpWebCrawler\Converter\Collection\Implode;
use Ixnode\PhpWebCrawler\Converter\Collection\RemoveEmpty;
use Ixnode\PhpWebCrawler\Converter\Scalar\Base\Converter;
use Ixnode\PhpWebCrawler\Converter\Scalar\Boolean;
use Ixnode\PhpWebCrawler\Converter\Scalar\Number;
use Ixnode\PhpWebCrawler\Converter\Scalar\PregMatch;
use Ixnode\PhpWebCrawler\Converter\Scalar\Replace;
use Ixnode\PhpWebCrawler\Converter\Scalar\Sprintf;
use Ixnode\PhpWebCrawler\Converter\Scalar\ToLower;
use Ixnode\PhpWebCrawler\Converter\Scalar\Trim;
use Ixnode\PhpWebCrawler\Output\Field;
use Ixnode\PhpWebCrawler\Output\Group;
use Ixnode\PhpWebCrawler\Source\Url;
use Ixnode\PhpWebCrawler\Source\XpathSections;
use Ixnode\PhpWebCrawler\Value\LastUrl;
use Ixnode\PhpWebCrawler\Value\XpathTextNode;
use JsonException;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WikipediaAirportCrawlerCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-24)
 * @since 0.1.0 (2024-02-24) First version.
 *
 * @example bin/console crawler:wikipedia:airport
 */
class WikipediaAirportCrawlerCommand extends Command
{
    protected static $defaultName = 'crawler:wikipedia:airport';

    private const ZERO_RESULT = 0;

    private const NUMBER_1 = 1;

    private const SEPARATOR_COUNT = 20;

    private const RUNWAY_CHUNK_SIZE = 3;

    private const RUNWAY_SEPARATOR = ', ';

    private const DOMAIN_WIKIPEDIA = 'https://en.wikipedia.org';

    private OutputInterface $output;

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setName(strval(self::$defaultName))
            ->setDescription('Crawls wikipedia pages for airport properties.')
            ->setDefinition([
                new InputArgument(KeyArray::IATA, InputArgument::REQUIRED, 'The iata code to be parsed.'),
            ])
            ->setHelp(
                <<<'EOT'

The <info>crawler:wikipedia:airport</info> crawls wikipedia pages for airport properties.

EOT
            );
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
     * @return Field[]|Group[]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getFieldsAirport(): array
    {
        $xpathRunwaysTable = '/html/body//tr/th[contains(@class, "infobox-header") and contains(., "Runways")]/ancestor::tr/following-sibling::tr[1]/td[contains(@class, "infobox-full-data")]/table%s';

        return [
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
                    converters: [new Number(['.', ','], '')]
                ),
                $this->getField(
                    key: 'movements',
                    search: ['aircraft movements', 'movements', 'aircraft operations'],
                    searchNot: 'change',
                    converters: [new Number(['.', ','], '')]
                ),
                $this->getField(
                    key: 'cargo',
                    search: ['cargo'],
                    searchNot: 'change',
                    converters: [new Number(['.', ','], '')]
                ),
                $this->getField(
                    key: 'website',
                    search: 'website',
                    converters: [new Trim()],
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
                    '/html/body//tr/th[contains(@class, "infobox-header") and contains(., "Statistics")]/text()',
                    new Trim(),
                    new PregMatch('~Statistics \(([0-9]+)\)~', 1)
                )),
                new Field('runways', new XpathTextNode(
                    sprintf($xpathRunwaysTable, '//tr[position() > 2]/td[position() = 1 or position() = 2 or position() = 4]'),
                    new Trim(),
                    new Chunk(
                        chunkSize: self::RUNWAY_CHUNK_SIZE,
                        separator: self::RUNWAY_SEPARATOR,
                        arrayKeys: ['direction', 'length', 'surface'],
                        scalarConverters: [null, new Number([',', '.'], ''), new ToLower()])
                    )
                ),
            ),

            new Group(
                'general',
                $this->getField(
                    key: 'elevation',
                    search: 'elevation',
                    converters: [new Trim(), new PregMatch('~([0-9]+(?:[,.][0-9]+)?)(?:[\xc2\xa0 ]+)m~', 1), new Number([',', '.'], '')]
                )
            )
        ];
    }

    /**
     * Returns the airport wikipedia page from wikipedia search.
     *
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
    private function getPage(string $iata): Json|null
    {
        $site = sprintf('%s/wiki/%s', self::DOMAIN_WIKIPEDIA, $iata);

        $url = new Url(
            $site,
            new Field('title', new XpathTextNode('/html/head/title')),
            new Field('last-url', new LastUrl()),
            new Field('is-list-page', new XpathTextNode('/html/body//*[@id="mw-content-text"]/div/p[contains(., "may refer to:") or contains(., "can refer to:") or contains(., "may also refer to:")]', new Boolean())),
            new Group(
                'hits',
                new XpathSections(
                    '/html/body//div[@id="mw-content-text"]//ul/li[contains(translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "airport") or contains(translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "iata")]',
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
                ...$this->getFieldsAirport()
            ),
        );

        $parsed = $url->parse();

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
    private function doGetPageData(Json $parsed): Json
    {
        if (!$parsed->getKeyBoolean('is-list-page')) {
            return $parsed->getKeyJson(['data']);
        }

        $link = $this->getPropertyLink($parsed);

        $url = new Url(
            $link,
            ...$this->getFieldsAirport()
        );

        return $url->parse();
    }

    /**
     * Returns the parsed wikipedia data.
     *
     * @param Json $parsed
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
    private function getPageData(Json $parsed): Json
    {
        $pageData = $this->doGetPageData($parsed);

        $airportOperatorComplex = $pageData->getKey(['airport', 'operator-complex']);
        $airportOperator = $pageData->getKey(['airport', 'operator']);

        if (!is_string($airportOperatorComplex) && !is_null($airportOperatorComplex)) {
            throw new TypeInvalidException(sprintf('Unsupported type for airport-operator-complex: %s', gettype($airportOperatorComplex)));
        }

        if (!is_string($airportOperator) &&!is_null($airportOperator)) {
            throw new TypeInvalidException(sprintf('Unsupported type for airport-operator: %s', gettype($airportOperator)));
        }

        $operators = [];

        if (is_string($airportOperatorComplex)) {
            $operators[] = $airportOperatorComplex;
        }

        if (is_string($airportOperator)) {
            $operators[] = $airportOperator;
        }

        $airportOperator = count($operators) > self::ZERO_RESULT ? implode(', ', $operators) : null;

        $pageData->addValue(['airport', 'operator'], $airportOperator);
        $pageData->deleteKey(['airport', 'operator-complex']);

        return $pageData;
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
     */
    private function doIata(string $iata): int
    {
        $iata = strtoupper($iata);

        $parsed = $this->getPage($iata);

        if (is_null($parsed)) {
            $this->output->writeln(sprintf('<error>No airport page found for %s.</error>', $iata));
            return Command::FAILURE;
        }

        $isListPage = $parsed->getKeyBoolean('is-list-page');

        /* Use the first hit or the title and the last url of the page. */
        $name = $this->getPropertyName($parsed);
        $link = $this->getPropertyLink($parsed);
        $numberHits = $isListPage ? count($parsed->getKeyArray('hits')) : self::NUMBER_1;

        /* Get the airport data. */
        $data = $this->getPageData($parsed);
        $airportIataConfirmed = $data->hasKey(['airport', 'iata']) && $data->getKey(['airport', 'iata']) === $iata;

        /* Last URL */
        $lastUrl = $parsed->getKey('last-url');
        $lastUrl = is_string($lastUrl) ? $lastUrl  : null;

        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln(sprintf('<info>Search for iata code "%s"</info>', $iata));
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln(sprintf('Hits:   %d', $numberHits));
        $this->output->writeln(sprintf('Type:   %s', $isListPage ? sprintf('%d found via list search from "%s"', $numberHits, $lastUrl) : 'Direct Page'));
        $this->output->writeln(str_repeat('-', self::SEPARATOR_COUNT));
        $this->output->writeln(sprintf('IATA:   %s', $iata));
        $this->output->writeln(sprintf('Name:   %s', $name));
        $this->output->writeln(sprintf('Link:   %s', $link));
        $this->output->writeln(str_repeat('-', self::SEPARATOR_COUNT));
        $this->output->writeln('Data:');
        $this->output->writeln($data->getJsonStringFormatted());
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln($airportIataConfirmed ? '<info>IATA confirmed.</info>' : '<error>IATA not confirmed.</error>');
        $this->output->writeln(str_repeat('=', self::SEPARATOR_COUNT));
        $this->output->writeln('');

        return $airportIataConfirmed ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Execute the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $iata = $input->getArgument(KeyArray::IATA);

        if (!is_string($iata)) {
            throw new LogicException('Unsupported type of iata given.');
        }

        $iatas = [$iata];

        $this->output->writeln('');
        foreach ($iatas as $iata) {
            $return = $this->doIata($iata);

            if ($return !== Command::SUCCESS) {
                $this->output->writeln('');
                return $return;
            }
        }

        $this->output->writeln('');
        return Command::SUCCESS;
    }
}
