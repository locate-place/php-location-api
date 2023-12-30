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

namespace App\Command\Location;

use App\ApiPlatform\Resource\Location;
use App\Command\Base\Base;
use App\Entity\Location as LocationEntity;
use App\Repository\LocationRepository;
use App\Service\LocationService;
use App\Service\LocationServiceDebug;
use App\Utils\Version\Version;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpTimezone\Constants\Language;
use JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * Class CoordinateCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-27)
 * @since 0.1.0 (2023-06-27) First version.
 * @example bin/console location:coordinate [coordinate]
 * @example bin/console location:coordinate 51.0504 13.7373
 */
class CoordinateCommand extends Base
{
    final public const COMMAND_NAME = 'location:coordinate';

    final public const ARGUMENT_NAME_LATITUDE = 'latitude';

    final public const ARGUMENT_NAME_LONGITUDE = 'longitude';

    private const OPTION_NAME_VERBOSE = 'verbose';

    private const OPTION_NAME_DEBUG = 'debug';

    private const OPTION_NAME_DEBUG_LIMIT = 'debug-limit';

    private const OPTION_NAME_FORMAT = 'format';

    private const OPTION_ISO_LANGUAGE = 'iso-language';

    private const FORMAT_JSON = 'json';

    private const FORMAT_PHP = 'php';

    private const FORMATS = [
        self::FORMAT_JSON,
        self::FORMAT_PHP,
    ];

    private readonly Serializer $serializer;

    /**
     * @param LocationRepository $locationRepository
     * @param LocationService $locationService
     * @param ParameterBagInterface $parameterBag
     * @param LocationServiceDebug|null $locationServiceDebug
     */
    public function __construct(
        protected LocationRepository $locationRepository,
        protected LocationService $locationService,
        protected ParameterBagInterface $parameterBag,
        protected LocationServiceDebug|null $locationServiceDebug = null
    )
    {
        $this->serializer = $this->getSerializer();

        parent::__construct();
    }

    /**
     * Returns the symfony serializer.
     *
     * @return Serializer
     */
    private function getSerializer(): Serializer
    {
        $normalizers = [new ObjectNormalizer()];
        $encoders = [new JsonEncoder()];
        return new Serializer($normalizers, $encoders);
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Returns some information about the given coordinate.')
            ->setDefinition([
                new InputArgument(self::ARGUMENT_NAME_LATITUDE, InputArgument::REQUIRED, 'The latitude of the coordinate.'),
                new InputArgument(self::ARGUMENT_NAME_LONGITUDE, InputArgument::OPTIONAL, 'The longitude of the coordinate.'),
            ])
            ->addOption(self::OPTION_NAME_FORMAT, 'f', InputOption::VALUE_REQUIRED, 'Sets the output format.', 'json')
            ->addOption(self::OPTION_ISO_LANGUAGE, 'i', InputOption::VALUE_REQUIRED, 'Sets the output language.', 'en')
            ->addOption(self::OPTION_NAME_DEBUG, 'd', InputOption::VALUE_NONE, 'Shows debug information.')
            ->addOption(self::OPTION_NAME_DEBUG_LIMIT, 'l', InputOption::VALUE_REQUIRED, 'Sets the debug limit.', LocationServiceDebug::DEBUG_LIMIT)
            ->setHelp(
                <<<'EOT'

The <info>location:coordinate</info> command returns some information about the given coordinate.

EOT
            );
    }

    /**
     * Builds the output JSON representation.
     *
     * @param Location $location
     * @param string $coordinateString
     * @param string $isoLanguage
     * @return Json
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    private function getJson(Location $location, string $coordinateString, string $isoLanguage): Json
    {
        $jsonContent = $this->serializer->serialize($location, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['meters']]);
        $json = new Json($jsonContent);

        $coordinate = new Coordinate($coordinateString);

        $duration = microtime(true) - $this->locationService->getTimeStart();

        $languageValues = array_key_exists($isoLanguage, Language::LANGUAGE_ISO_639_1) ?
            Language::LANGUAGE_ISO_639_1[$isoLanguage] :
            null
        ;

        $data = [
            'data' => $json->getArray(),
            'given' => [
                'coordinate' => [
                    'raw' => $coordinateString,
                    'parsed' => [
                        'latitude' => $coordinate->getLatitude(),
                        'longitude' => $coordinate->getLongitude(),
                        'latitudeDms' => $coordinate->getLatitudeDms(),
                        'longitudeDms' => $coordinate->getLongitudeDms(),
                    ],
                ],
                'language' => [
                    'raw' => $isoLanguage,
                    'parsed' => [
                        'name' => !is_null($languageValues) ? $languageValues['en'] : 'n/a',
                    ]
                ],
            ],
            'time-taken' => sprintf(
                '%dms',
                $duration * 1000
            ),
            'version' => (new Version())->getVersion(),
            'data-licence' => [
                'full' => (new TypeCastingHelper($this->parameterBag->get('data_license_full')))->strval(),
                'short' => (new TypeCastingHelper($this->parameterBag->get('data_license_short')))->strval(),
                'url' => (new TypeCastingHelper($this->parameterBag->get('data_license_url')))->strval(),
            ],
        ];

        return new Json($data);
    }

    /**
     * Prints debug information.
     *
     * @param Coordinate $coordinate
     * @param string $isoLanguage
     * @param int $debugLimit
     * @return void
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws NonUniqueResultException
     */
    private function debug(Coordinate $coordinate, string $isoLanguage, int $debugLimit): void
    {
        if (is_null($this->locationServiceDebug)) {
            return;
        }

        $this->locationService->setCoordinate($coordinate);
        $location = $this->locationService->getLocationEntityByCoordinate($coordinate);

        /* No location was found for given coordinate. */
        if (!$location instanceof LocationEntity) {
            $this->output->writeln(sprintf('No location found for coordinate "%s".', $coordinate->getRaw()));
            return;
        }

        $this->locationServiceDebug->startMeasurement();
        $this->locationServiceDebug->setDebugLimit($debugLimit);
        $this->locationServiceDebug->setCoordinate($coordinate);
        $this->locationServiceDebug->setOutput($this->output);
        $this->locationServiceDebug->setLocationContainer($this->locationService->getServiceLocationContainerFromLocationRepository($location));
        $this->locationServiceDebug->printDebug($location, $isoLanguage);
    }

    /**
     * Execute the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input = $input;

        $latitude = (new TypeCastingHelper($input->getArgument(self::ARGUMENT_NAME_LATITUDE)))->strval();
        $longitude = $input->hasArgument(self::ARGUMENT_NAME_LONGITUDE) ? (new TypeCastingHelper($input->getArgument(self::ARGUMENT_NAME_LONGITUDE)))->strval() : '';
        $coordinateString = trim(sprintf('%s %s', $latitude, $longitude));

        $verbose = (bool) $input->getOption(self::OPTION_NAME_VERBOSE);
        $debug = (bool) $input->getOption(self::OPTION_NAME_DEBUG);
        $debugLimit = (new TypeCastingHelper($input->getOption(self::OPTION_NAME_DEBUG_LIMIT)))->intval();
        $format = (new TypeCastingHelper($input->getOption(self::OPTION_NAME_FORMAT)))->strval();
        $isoLanguage = (new TypeCastingHelper($input->getOption(self::OPTION_ISO_LANGUAGE)))->strval();

        if (!in_array($isoLanguage, array_keys(Language::LANGUAGE_ISO_639_1))) {
            $this->output->writeln(sprintf(
                '<error>%s</error> is not a valid ISO 639-1 language code.',
                $isoLanguage
            ));
            return Command::INVALID;
        }

        if (!in_array($format, self::FORMATS, true)) {
            $this->output->writeln(sprintf(
                '<error>Invalid given format "%s". Allowed: "%s"</error>',
                $format,
                implode('", "', self::FORMATS),
            ));
            return Command::INVALID;
        }

        $coordinate = new Coordinate($coordinateString);

        if ($debug) {
            $this->debug($coordinate, $isoLanguage, $debugLimit);
            return Command::SUCCESS;
        }

        $location = $this->locationService->getLocationByCoordinate($coordinate, $isoLanguage);

        $json = $this->getJson($location, $coordinateString, $isoLanguage);

        if (!$verbose) {
            $message = match ($format) {
                self::FORMAT_JSON => $json->getJsonStringFormatted(),
                self::FORMAT_PHP => var_export($json->getArray(), true),
            };

            $this->output->writeln($message);
            return Command::SUCCESS;
        }

        $this->printAndLog(sprintf('Given coordinate:             %s', $coordinate->getRaw()));
        $this->printAndLog(sprintf('Parsed coordinate (decimal):  %f %f', $coordinate->getLatitude(), $coordinate->getLongitude()));
        $this->printAndLog(sprintf('Parsed coordinate (dms):      %s %s', $coordinate->getLatitudeDMS(), $coordinate->getLongitudeDMS()));
        $this->printAndLog($json->getJsonStringFormatted());

        /* Command successfully executed. */
        return Command::SUCCESS;
    }
}
