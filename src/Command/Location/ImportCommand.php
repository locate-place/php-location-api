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

use App\Command\Base\BaseLocationImport;
use App\Constants\Key\KeyCamelCase;
use App\DBAL\GeoLocation\ValueObject\Point;
use App\Entity\AdminCode;
use App\Entity\Country;
use App\Entity\FeatureClass;
use App\Entity\FeatureCode;
use App\Entity\Import;
use App\Entity\Location;
use App\Entity\Timezone;
use App\Repository\ImportRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpContainer\File;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpTimezone\Country as IxnodeCountry;
use Ixnode\PhpTimezone\Timezone as IxnodeTimezone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * Class ImportCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-27)
 * @since 0.1.0 (2023-06-27) First version.
 * @example bin/console location:import [file]
 * @example bin/console location:import import/location/DE.txt
 * @download bin/console location:download [countryCode]
 * @see http://download.geonames.org/export/dump/
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ImportCommand extends BaseLocationImport
{
    protected static $defaultName = 'location:import';

    private int $ignoredLines = 0;

    /** @var array<int, string> $ignoredLinesText */
    private array $ignoredLinesText = [];

    /** @var array<string, array<int, string>> $unknownTimezones */
    private array $unknownTimezones = [];

    /** @var array<string, array<int, string>> $invalidTimezones */
    private array $invalidTimezones = [];

    /** @var array<int, string> $invalidNameValues */
    private array $invalidNameValues = [];

    /** @var array<int, string> $invalidAsciiNameValues */
    private array $invalidAsciiNameValues = [];

    /** @var array<int, string> $invalidAlternateNamesValues */
    private array $invalidAlternateNamesValues = [];

    /** @var array<int, string> $invalidCc2Values */
    private array $invalidCc2Values = [];

    /** @var array<string, Country> $countries */
    private array $countries = [];

    /** @var array<string, FeatureClass> $featureClasses */
    private array $featureClasses = [];

    /** @var array<string, FeatureCode> $featureCodes */
    private array $featureCodes = [];

    /** @var array<string, Timezone> $timezones */
    private array $timezones = [];

    /** @var array<string, AdminCode> $adminCodes */
    private array $adminCodes = [];

    protected const FIELD_GEONAME_ID = 'geoname-id';

    protected const FIELD_NAME = 'name';

    protected const FIELD_ASCII_NAME = 'ascii-name';

    protected const FIELD_ALTERNATE_NAMES = 'alternate-names';

    protected const FIELD_LATITUDE = 'latitude';

    protected const FIELD_LONGITUDE = 'longitude';

    protected const FIELD_FEATURE_CLASS = 'feature-class';

    protected const FIELD_FEATURE_CODE = 'feature-code';

    protected const FIELD_COUNTRY_CODE = 'country-code';

    protected const FIELD_CC2 = 'cc2';

    protected const FIELD_ADMIN1 = 'admin1';

    protected const FIELD_ADMIN2 = 'admin2';

    protected const FIELD_ADMIN3 = 'admin3';

    protected const FIELD_ADMIN4 = 'admin4';

    protected const FIELD_POPULATION = 'population';

    protected const FIELD_ELEVATION = 'elevation';

    protected const FIELD_DEM = 'dem';

    protected const FIELD_TIMEZONE = 'timezone';

    protected const FIELD_MODIFICATION_DATE = 'modification-date';

    protected const TEXT_IMPORT_START = 'Start importing "%s" - [%d/%d]. Please wait.';

    private const TEXT_ROWS_WRITTEN = '%d rows written to table %s (%d checked): %.2fs';

    protected const TEXT_WARNING_IGNORED_LINE = 'Ignored line: %s:%d';

    protected const OPTION_NAME_FORCE = 'force';

    protected bool $checkCommandExecution = false;

    protected bool $errorFound = false;

    protected float $timeStart;

    protected int $importedRows = 0;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager
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
            ->setDescription('Imports locations from given file.')
            ->setDefinition([
                new InputArgument('file', InputArgument::REQUIRED, 'The file to be imported.'),
            ])
            ->addOption(self::OPTION_NAME_FORCE, 'f', InputOption::VALUE_NONE, 'Forces the import even if an import with the same given country code already exists.')
            ->setHelp(
                <<<'EOT'

The <info>import:location</info> command imports locations from a given file.

EOT
            );
    }

    /**
     * @inheritdoc
     */
    protected function getFieldTranslation(): array
    {
        return [
            'GeonameId' => self::FIELD_GEONAME_ID,
            'Name' => self::FIELD_NAME,
            'AsciiName' => self::FIELD_ASCII_NAME,
            'AlternateNames' => self::FIELD_ALTERNATE_NAMES,
            'Latitude' => self::FIELD_LATITUDE,
            'Longitude' => self::FIELD_LONGITUDE,
            'FeatureClass' => self::FIELD_FEATURE_CLASS,
            'FeatureCode' => self::FIELD_FEATURE_CODE,
            'CountryCode' => self::FIELD_COUNTRY_CODE,
            'Cc2' => self::FIELD_CC2,
            'Admin1' => self::FIELD_ADMIN1,
            'Admin2' => self::FIELD_ADMIN2,
            'Admin3' => self::FIELD_ADMIN3,
            'Admin4' => self::FIELD_ADMIN4,
            'Population' => self::FIELD_POPULATION,
            'Elevation' => self::FIELD_ELEVATION,
            'Dem' => self::FIELD_DEM,
            'Timezone' => self::FIELD_TIMEZONE,
            'ModificationDate' => self::FIELD_MODIFICATION_DATE,
        ];
    }

    /**
     * Returns some extra rows.
     *
     * @inheritdoc
     */
    protected function getExtraCsvRows(): array
    {
        return [];
    }

    /**
     * Translate given value according to given index name.
     *
     * @param bool|int|string $value
     * @param string $indexName
     * @return bool|string|int|float|DateTimeImmutable|null
     * @throws TypeInvalidException
     */
    protected function translateField(bool|int|string $value, string $indexName): bool|string|int|float|null|DateTimeImmutable
    {
        return match($indexName) {
            self::FIELD_GEONAME_ID,
            self::FIELD_ELEVATION,
            self::FIELD_DEM => $this->trimInteger($value),

            self::FIELD_NAME,
            self::FIELD_ASCII_NAME,
            self::FIELD_ALTERNATE_NAMES,
            self::FIELD_FEATURE_CLASS,
            self::FIELD_FEATURE_CODE,
            self::FIELD_COUNTRY_CODE,
            self::FIELD_CC2,
            self::FIELD_POPULATION,
            self::FIELD_TIMEZONE,
            self::FIELD_MODIFICATION_DATE => $this->trimString($value),

            self::FIELD_LATITUDE,
            self::FIELD_LONGITUDE => $this->trimFloat($value),

            self::FIELD_ADMIN1,
            self::FIELD_ADMIN2,
            self::FIELD_ADMIN3,
            self::FIELD_ADMIN4 => !empty($value) ? $this->trimString($value) : null,

            default => $value,
        };
    }

    /**
     * Returns the converted row.
     *
     * @inheritdoc
     * @throws TypeInvalidException
     * @throws ClassInvalidException
     */
    protected function getDataRow(array $row, array $header, File $csv, int $line): ?array
    {
        if (count($row) !== count($header)) {
            $this->ignoredLines++;
            $this->ignoredLinesText[] = sprintf('%s:%d', $csv->getPath(), $line);
            $this->printAndLog(sprintf(
                self::TEXT_WARNING_IGNORED_LINE,
                $csv->getPath(),
                $line
            ));
            return null;
        }

        $dataRow = [];

        foreach ($row as $index => $value) {
            $indexName = $header[$index];

            if ($indexName === null) {
                continue;
            }

            $dataRow[$indexName] = $this->translateField($value, $indexName);
        }

        return $dataRow;
    }

    /**
     * Returns or creates a new Country entity.
     *
     * @param string $code
     * @return Country
     * @throws ArrayKeyNotFoundException
     */
    protected function getCountry(string $code): Country
    {
        $index = $code;

        /* Use cache. */
        if (array_key_exists($index, $this->countries)) {
            return $this->countries[$index];
        }

        $repository = $this->entityManager->getRepository(Country::class);

        $country = $repository->findOneBy([
            KeyCamelCase::CODE => $code,
        ]);

        /* Create new entity. */
        if (!$country instanceof Country) {
            $country = (new Country())
                ->setCode($code)
                ->setName((new IxnodeCountry($code))->getName())
            ;
            $this->entityManager->persist($country);
        }

        $this->countries[$index] = $country;
        return $country;
    }

    /**
     * Returns or creates a new FeatureClass entity.
     *
     * @param string $class
     * @return FeatureClass
     */
    protected function getFeatureClass(string $class): FeatureClass
    {
        $index = $class;

        /* Use cache. */
        if (array_key_exists($index, $this->featureClasses)) {
            return $this->featureClasses[$index];
        }

        $repository = $this->entityManager->getRepository(FeatureClass::class);

        $featureClass = $repository->findOneBy([
            KeyCamelCase::CLASS_KEY => $class,
        ]);

        /* Create new entity. */
        if (!$featureClass instanceof FeatureClass) {
            $featureClass = (new FeatureClass())
                ->setClass($class)
            ;
            $this->entityManager->persist($featureClass);
        }

        $this->featureClasses[$index] = $featureClass;
        return $featureClass;
    }

    /**
     * Returns or creates a new FeatureCode entity.
     *
     * @param FeatureClass $class
     * @param string $code
     * @return FeatureCode
     */
    protected function getFeatureCode(FeatureClass $class, string $code): FeatureCode
    {
        $index = sprintf('%s_:%s', $class->getClass(), $code);

        /* Use cache. */
        if (array_key_exists($index, $this->featureCodes)) {
            return $this->featureCodes[$index];
        }

        $repository = $this->entityManager->getRepository(FeatureCode::class);

        $featureCode = $repository->findOneBy([
            KeyCamelCase::CLASS_KEY => $class,
            KeyCamelCase::CODE => $code,
        ]);

        /* Create new entity. */
        if (!$featureCode instanceof FeatureCode) {
            $featureCode = (new FeatureCode())
                ->setClass($class)
                ->setCode($code)
            ;
            $this->entityManager->persist($featureCode);
        }

        $this->featureCodes[$index] = $featureCode;
        return $featureCode;
    }

    /**
     * Returns or creates a new Timezone entity.
     *
     * @param string $timezoneValue
     * @return Timezone
     * @throws ArrayKeyNotFoundException
     */
    protected function getTimezone(string $timezoneValue): Timezone
    {
        $index = $timezoneValue;

        /* Use cache. */
        if (array_key_exists($index, $this->timezones)) {
            return $this->timezones[$index];
        }

        $repository = $this->entityManager->getRepository(Timezone::class);

        $timezone = $repository->findOneBy([
            KeyCamelCase::TIMEZONE => $timezoneValue,
        ]);

        /* Create new entity. */
        if (!$timezone instanceof Timezone) {
            $countryCode = (new IxnodeTimezone($timezoneValue))->getCountryCode();

            $country = $this->getCountry($countryCode);

            $timezone = (new Timezone())
                ->setTimezone($timezoneValue)
                ->setCountry($country)
            ;
            $this->entityManager->persist($timezone);
        }

        $this->timezones[$index] = $timezone;
        return $timezone;
    }

    /**
     * Returns or creates a new Timezone entity.
     *
     * @param Country $country
     * @param string|null $admin1Code
     * @param string|null $admin2Code
     * @param string|null $admin3Code
     * @param string|null $admin4Code
     * @return AdminCode
     */
    protected function getAdminCode(
        Country $country,
        ?string $admin1Code,
        ?string $admin2Code,
        ?string $admin3Code,
        ?string $admin4Code
    ): AdminCode
    {
        $index = sprintf(
            '%s_:%s_:%s_:%s_:%s',
            $country->getCode(),
            $admin1Code,
            $admin2Code,
            $admin3Code,
            $admin4Code
        );

        /* Use cache. */
        if (array_key_exists($index, $this->adminCodes)) {
            return $this->adminCodes[$index];
        }

        $repository = $this->entityManager->getRepository(AdminCode::class);

        $adminCode = $repository->findOneBy([
            KeyCamelCase::COUNTRY => $country,
            KeyCamelCase::ADMIN_1_CODE => $admin1Code,
            KeyCamelCase::ADMIN_2_CODE => $admin2Code,
            KeyCamelCase::ADMIN_3_CODE => $admin3Code,
            KeyCamelCase::ADMIN_4_CODE => $admin4Code,
        ]);

        /* Create new entity. */
        if (!$adminCode instanceof AdminCode) {
            $adminCode = (new AdminCode())
                ->setCountry($country)
                ->setAdmin1Code($admin1Code)
                ->setAdmin2Code($admin2Code)
                ->setAdmin3Code($admin3Code)
                ->setAdmin4Code($admin4Code)
            ;
            $this->entityManager->persist($adminCode);
        }

        $this->adminCodes[$index] = $adminCode;
        return $adminCode;
    }

    /**
     * Returns the Import class.
     *
     * @param File $file
     * @return Import
     * @throws ArrayKeyNotFoundException
     */
    protected function getImport(File $file): Import
    {
        $countryCode = basename($file->getPath(), '.txt');

        $country = $this->getCountry($countryCode);

        $import = (new Import())
            ->setCountry($country)
            ->setPath($file->getPath())
            ->setExecutionTime(0)
            ->setRows(0)
        ;

        $this->entityManager->persist($import);
        $this->entityManager->flush();

        return $import;
    }

    /**
     * Returns or creates a new Location entity.
     *
     * @param int $geonameId
     * @param string $name
     * @param string $asciiName
     * @param string $alternateNames
     * @param float $latitude
     * @param float $longitude
     * @param string $cc2
     * @param string $population
     * @param int $elevation
     * @param int $dem
     * @param string $modificationDate
     * @param Country $country
     * @param FeatureClass $featureClass
     * @param FeatureCode $featureCode
     * @param Timezone $timezone
     * @param AdminCode $adminCode
     * @return Location
     * @throws Exception
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected function getLocation(
        int $geonameId,
        string $name,
        string $asciiName,
        string $alternateNames,
        float $latitude,
        float $longitude,
        string $cc2,
        string $population,
        int $elevation,
        int $dem,
        string $modificationDate,
        Country $country,
        FeatureClass $featureClass,
        FeatureCode $featureCode,
        Timezone $timezone,
        AdminCode $adminCode
    ): Location
    {
        $repository = $this->entityManager->getRepository(Location::class);

        $location = $repository->findOneBy([
            KeyCamelCase::GEONAME_ID => $geonameId,
        ]);

        /* Create new entity. */
        if (!$location instanceof Location) {
            $location = (new Location())
                ->setGeonameId($geonameId)
            ;
        }

        $coordinate = new Point($latitude, $longitude);

        /* Update entity. */
        $location
            ->setName($name)
            ->setAsciiName($asciiName)
            ->setAlternateNames($alternateNames)
            ->setCoordinate($coordinate)
            ->setCc2($cc2)
            ->setPopulation($population)
            ->setElevation($elevation)
            ->setDem($dem)
            ->setModificationDate(new DateTime($modificationDate))
            ->setCountry($country)
            ->setFeatureClass($featureClass)
            ->setFeatureCode($featureCode)
            ->setTimezone($timezone)
            ->setAdminCode($adminCode)
        ;

        $this->entityManager->persist($location);

        return $location;
    }

    /**
     * Sets the update_at field of Import entity.
     *
     * @return void
     */
    private function updateImportEntity(): void
    {
        if (!isset($this->import)) {
            return;
        }

        $executionTime = (int) round(microtime(true) - $this->timeStart);

        $this->import
            ->setUpdatedAt(new DateTimeImmutable())
            ->setExecutionTime($executionTime)
            ->setRows($this->importedRows)
        ;
        $this->entityManager->persist($this->import);
        $this->entityManager->flush();
    }

    /**
     * Saves the data as entities.
     *
     * @param array<int, array<string, mixed>> $data
     * @return int
     * @throws Exception
     */
    protected function saveEntities(array $data): int
    {
        $writtenRows = 0;

        /* Update or create entities. */
        foreach ($data as $row) {
            $geonameIdValue = (new TypeCastingHelper($row[self::FIELD_GEONAME_ID]))->intval();
            $nameValue = (new TypeCastingHelper($row[self::FIELD_NAME]))->strval();
            $asciiNameValue = (new TypeCastingHelper($row[self::FIELD_ASCII_NAME]))->strval();
            $alternateNamesValue = (new TypeCastingHelper($row[self::FIELD_ALTERNATE_NAMES]))->strval();
            $latitudeValue = (new TypeCastingHelper($row[self::FIELD_LATITUDE]))->floatval();
            $longitudeValue = (new TypeCastingHelper($row[self::FIELD_LONGITUDE]))->floatval();
            $cc2Value = (new TypeCastingHelper($row[self::FIELD_CC2]))->strval();
            $populationValue = (new TypeCastingHelper($row[self::FIELD_POPULATION]))->strval();
            $elevationValue = (new TypeCastingHelper($row[self::FIELD_ELEVATION]))->intval();
            $demValue = (new TypeCastingHelper($row[self::FIELD_DEM]))->intval();
            $modificationDateValue = (new TypeCastingHelper($row[self::FIELD_MODIFICATION_DATE]))->strval();

            $admin1CodeValue = empty($row[self::FIELD_ADMIN1]) ? null : (new TypeCastingHelper($row[self::FIELD_ADMIN1]))->strval();
            $admin2CodeValue = empty($row[self::FIELD_ADMIN2]) ? null : (new TypeCastingHelper($row[self::FIELD_ADMIN2]))->strval();
            $admin3CodeValue = empty($row[self::FIELD_ADMIN3]) ? null : (new TypeCastingHelper($row[self::FIELD_ADMIN3]))->strval();
            $admin4CodeValue = empty($row[self::FIELD_ADMIN4]) ? null : (new TypeCastingHelper($row[self::FIELD_ADMIN4]))->strval();

            $countryValue = (new TypeCastingHelper($row[self::FIELD_COUNTRY_CODE]))->strval();
            $featureClassValue = (new TypeCastingHelper($row[self::FIELD_FEATURE_CLASS]))->strval();
            $featureCodeValue = (new TypeCastingHelper($row[self::FIELD_FEATURE_CODE]))->strval();
            $timezoneValue = (new TypeCastingHelper($row[self::FIELD_TIMEZONE]))->strval();

            $country = $this->getCountry($countryValue);
            $featureClass = $this->getFeatureClass($featureClassValue);
            $featureCode = $this->getFeatureCode($featureClass, $featureCodeValue);
            $timezone = $this->getTimezone($timezoneValue);
            $adminCode = $this->getAdminCode($country, $admin1CodeValue, $admin2CodeValue, $admin3CodeValue, $admin4CodeValue);

            $location = $this->getLocation(
                $geonameIdValue,
                $nameValue,
                $asciiNameValue,
                $alternateNamesValue,
                $latitudeValue,
                $longitudeValue,
                $cc2Value,
                $populationValue,
                $elevationValue,
                $demValue,
                $modificationDateValue,
                $country,
                $featureClass,
                $featureCode,
                $timezone,
                $adminCode
            );

            $location->addImport($this->import);

            $writtenRows++;
        }

        $this->printAndLog('Start flushing Location entities.');
        $this->entityManager->flush();

        return $writtenRows;
    }

    /**
     * Executes the given split file.
     *
     * @param File $file
     * @param int $numberCurrent
     * @param int $numberAll
     * @return void
     * @throws CaseInvalidException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws Exception
     */
    protected function doExecute(File $file, int $numberCurrent, int $numberAll): void
    {
        $this->printAndLog('---');
        $this->printAndLog(sprintf(self::TEXT_IMPORT_START, $file->getPath(), $numberCurrent, $numberAll));

        /* Reads the given csv files. */
        $this->printAndLog(sprintf('Start reading CSV file "%s"', $file->getPath()));
        $timeStart = microtime(true);
        $data = $this->readDataFromCsv($file, "\t");
        $timeExecution = (microtime(true) - $timeStart);
        $this->printAndLog(sprintf('%d rows successfully read: %.2fs (CSV file)', count($data), $timeExecution));

        /* Imports the Location data */
        $this->printAndLog('Start writing Location entities.');
        $timeStart = microtime(true);
        $rows = $this->saveEntities($data);
        $timeExecution = (microtime(true) - $timeStart);
        $this->printAndLog(sprintf(
            self::TEXT_ROWS_WRITTEN,
            $rows,
            'location',
            count($data),
            $timeExecution
        ));

        $this->importedRows += $rows;
    }

    /**
     * Executes the check command.
     *
     * @return int
     * @throws TypeInvalidException
     * @throws Exception
     */
    protected function executeCheckCommand(): int
    {
        $commandName = CheckCommand::$defaultName;

        $this->printAndLog('Check given file. Please wait...');

        /* Create application instance. */
        $application = $this->getApplication();

        if (is_null($application)) {
            throw new CaseUnsupportedException('Unable to create application.');
        }

        $application->setCatchExceptions(false);
        $application->setAutoExit(false);

        $file = (new TypeCastingHelper($this->input->getArgument('file')))->strval();

        $input = new ArrayInput([
            'command' => $commandName,
            'file' => $file,
        ]);

        /* Execute the command. */
        $returnValue = $application->run($input, $this->output);

        $message = match (true) {
            $returnValue === Command::SUCCESS => 'Done without errors. Continue with import.',
            default => 'Done with errors. Do not continue with import.',
        };

        $this->printAndLog($message);

        return $returnValue;
    }

    /**
     * Returns if the current import was already done.
     *
     * @param string $countryCode
     * @return bool
     * @throws TypeInvalidException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    protected function hasImportByCountryCode(string $countryCode): bool
    {
        $country = $this->entityManager->getRepository(Country::class)->findOneBy([
            'code' => strtoupper($countryCode),
        ]);

        if (!$country instanceof Country) {
            return false;
        }

        $repository = $this->entityManager->getRepository(Import::class);

        if (!$repository instanceof ImportRepository) {
            return false;
        }

        return $repository->getNumberOfImports($country) > 0;
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->timeStart = microtime(true);

        $this->output = $output;
        $this->input = $input;

        $force = $input->hasOption(self::OPTION_NAME_FORCE) && (bool) $input->getOption(self::OPTION_NAME_FORCE);

        $file = $this->getCsvFile('file');

        if (is_null($file)) {
            $this->printAndLog(sprintf('The given CSV file for "%s" does not exist.', $file));
            return Command::INVALID;
        }

        $countryCode = basename($file->getPath(), '.txt');

        if (!$force && $this->hasImportByCountryCode($countryCode)) {
            $this->printAndLog(sprintf('The given country code "%s" was already imported. Use --force to ignore.', $countryCode));
            return Command::INVALID;
        }

        $type = sprintf('%s/csv-import', $countryCode);

        $this->createLogInstanceFromFile($file, $type);

        if (!$this->checkCommandExecution) {
            $returnValue = $this->executeCheckCommand();
            if ($returnValue !== Command::SUCCESS) {
                return $returnValue;
            }
        }

        $this->clearTmpFolder($file, $countryCode);
        $this->setSplitLines(10000);
        $this->splitFile(
            $file,
            $countryCode,
            false,
            [
                'GeonameId',        /*  0 */
                'Name',             /*  1 */
                'AsciiName',        /*  2 */
                'AlternateNames',   /*  3 */
                'Latitude',         /*  4 */
                'Longitude',        /*  5 */
                'FeatureClass',     /*  6 */
                'FeatureCode',      /*  7 */
                'CountryCode',      /*  8 */
                'Cc2',              /*  9 */
                'Admin1',           /* 10 */
                'Admin2',           /* 11 */
                'Admin3',           /* 12 */
                'Admin4',           /* 13 */
                'Population',       /* 14 */
                'Elevation',        /* 15 */
                'Dem',              /* 16 */
                'Timezone',         /* 17 */
                'ModificationDate', /* 18 */
            ],
            "\t"
        );

        if (!$this->checkCommandExecution) {
            $this->import = $this->getImport($file);
        }

        /* Get tmp files */
        $splittedFiles = $this->getFilesTmp($file, $countryCode);

        /* Execute all splitted files */
        foreach ($splittedFiles as $index => $splittedFile) {
            $this->doExecute(new File($splittedFile), $index + 1, count($splittedFiles));
        }

        $this->errorFound = false;

        /* Show ignored lines */
        if ($this->getIgnoredLines() > 0) {
            $this->printAndLog('---');
            $this->printAndLog(sprintf('Ignored lines: %d', $this->getIgnoredLines()));
            foreach ($this->getIgnoredLinesText() as $ignoredLine) {
                $this->printAndLog(sprintf('- %s', $ignoredLine));
            }
            $this->errorFound = true;
        }

        /* Show unknown timezones */
        $unknownTimezones = $this->getUnknownTimezones();
        if (count($unknownTimezones) > 0) {
            $this->printAndLog('---');
            $this->printAndLog(sprintf('Unknown timezones: %d', count($unknownTimezones)));
            foreach ($unknownTimezones as $timezone => $unknownTimezoneFiles) {
                $this->printAndLog(sprintf('- %s', $timezone));
                foreach ($unknownTimezoneFiles as $unknownTimezoneFile) {
                    $this->printAndLog(sprintf('  - %s', $unknownTimezoneFile));
                }
            }
            $this->errorFound = true;
        }

        /* Show invalid timezones */
        $invalidTimezones = $this->getInvalidTimezones();
        if (count($invalidTimezones) > 0) {
            $this->printAndLog('---');
            $this->printAndLog(sprintf('Invalid timezones: %d', count($invalidTimezones)));
            foreach ($invalidTimezones as $timezone => $invalidTimezoneFiles) {
                $this->printAndLog(sprintf('- %s', $timezone));
                foreach ($invalidTimezoneFiles as $invalidTimezoneFile) {
                    $this->printAndLog(sprintf('  - %s', $invalidTimezoneFile));
                }
            }
            $this->errorFound = true;
        }

        /* Show invalid name values */
        $invalidNameValues = $this->getInvalidNameValues();
        if (count($invalidNameValues) > 0) {
            $this->printAndLog('---');
            $this->printAndLog(sprintf('Invalid name values: %d', count($invalidNameValues)));
            foreach ($invalidNameValues as $invalidNameValue) {
                $this->printAndLog(sprintf('- %s', $invalidNameValue));
            }
            $this->errorFound = true;
        }

        /* Show invalid ascii name values */
        $invalidAsciiNameValues = $this->getInvalidAsciiNameValues();
        if (count($invalidAsciiNameValues) > 0) {
            $this->printAndLog('---');
            $this->printAndLog(sprintf('Invalid ascii name values: %d', count($invalidAsciiNameValues)));
            foreach ($invalidAsciiNameValues as $invalidAsciiNameValue) {
                $this->printAndLog(sprintf('- %s', $invalidAsciiNameValue));
            }
            $this->errorFound = true;
        }

        /* Show invalid alternate name values */
        $invalidAlternateNamesValues = $this->getInvalidAlternateNamesValues();
        if (count($invalidAlternateNamesValues) > 0) {
            $this->printAndLog('---');
            $this->printAndLog(sprintf('Invalid alternate name values: %d', count($invalidAlternateNamesValues)));
            foreach ($invalidAlternateNamesValues as $invalidAlternateNamesValue) {
                $this->printAndLog(sprintf('- %s', $invalidAlternateNamesValue));
            }
            $this->errorFound = true;
        }

        /* Show invalid cc2 values */
        $invalidCc2Values = $this->getInvalidCc2Values();
        if (count($invalidCc2Values) > 0) {
            $this->printAndLog('---');
            $this->printAndLog(sprintf('Invalid cc2 values: %d', count($invalidCc2Values)));
            foreach ($invalidCc2Values as $invalidCc2Value) {
                $this->printAndLog(sprintf('- %s', $invalidCc2Value));
            }
            $this->errorFound = true;
        }

        if (!$this->errorFound) {
            $this->printAndLog('---');
            $this->printAndLog('Finish. No error was found.');
        }

        /* Set last date to Import entity. */
        $this->updateImportEntity();

        /* Command successfully executed. */
        return Command::SUCCESS;
    }

    /**
     * Returns the number of ignored lines.
     *
     * @return int
     */
    public function getIgnoredLines(): int
    {
        return $this->ignoredLines;
    }

    /**
     * Returns the ignored lines.
     *
     * @return array<int, string>
     */
    public function getIgnoredLinesText(): array
    {
        return $this->ignoredLinesText;
    }

    /**
     * Returns the unknown timezones.
     *
     * @return array<string, array<int, string>>
     */
    public function getUnknownTimezones(): array
    {
        return $this->unknownTimezones;
    }

    /**
     * Adds an unknown timezones.
     *
     * @param string $timezone
     * @param File $file
     * @param int $line
     * @return void
     */
    public function addUnknownTimezones(string $timezone, File $file, int $line): void
    {
        if (!array_key_exists($timezone, $this->unknownTimezones)) {
            $this->unknownTimezones[$timezone] = [];
        }

        $this->unknownTimezones[$timezone][] = sprintf('%s: %s', $file->getPath(), $line);
    }

    /**
     * Returns the invalid timezones.
     *
     * @return array<string, array<int, string>>
     */
    public function getInvalidTimezones(): array
    {
        return $this->invalidTimezones;
    }

    /**
     * Adds an invalid timezones.
     *
     * @param string $timezone
     * @param File $file
     * @param int $line
     * @return void
     */
    public function addInvalidTimezones(string $timezone, File $file, int $line): void
    {
        if (!array_key_exists($timezone, $this->invalidTimezones)) {
            $this->invalidTimezones[$timezone] = [];
        }

        $this->unknownTimezones[$timezone][] = sprintf('%s:%d', $file->getPath(), $line);
    }

    /**
     * Gets the invalid name values.
     *
     * @return array<int, string>
     */
    public function getInvalidNameValues(): array
    {
        return $this->invalidNameValues;
    }

    /**
     * Adds an invalid name value.
     *
     * @param string $nameValue
     * @param File $file
     * @param int $line
     * @param int $expectedMaxLength
     * @return void
     */
    public function addInvalidNameValues(string $nameValue, File $file, int $line, int $expectedMaxLength): void
    {
        $this->invalidNameValues[] = sprintf(
            '"%s" (max length %d, %d given) -> %s:%d',
            $nameValue,
            $expectedMaxLength,
            strlen($nameValue),
            $file->getPath(),
            $line
        );
    }

    /**
     * Gets the invalid ascii name values.
     *
     * @return array<int, string>
     */
    public function getInvalidAsciiNameValues(): array
    {
        return $this->invalidAsciiNameValues;
    }

    /**
     * Adds an invalid ascii name value.
     *
     * @param string $asciiNameValue
     * @param File $file
     * @param int $line
     * @param int $expectedMaxLength
     * @return void
     */
    public function addInvalidAsciiNameValues(string $asciiNameValue, File $file, int $line, int $expectedMaxLength): void
    {
        $this->invalidAsciiNameValues[] = sprintf(
            '"%s" (max length %d, %d given) -> %s:%d',
            $asciiNameValue,
            $expectedMaxLength,
            strlen($asciiNameValue),
            $file->getPath(),
            $line
        );
    }

    /**
     * Gets the invalid alternate name values.
     *
     * @return array<int, string>
     */
    public function getInvalidAlternateNamesValues(): array
    {
        return $this->invalidAlternateNamesValues;
    }

    /**
     * Adds an invalid ascii name value.
     *
     * @param string $alternateNameValue
     * @param File $file
     * @param int $line
     * @param int $expectedMaxLength
     * @return void
     */
    public function addInvalidAlternateNamesValues(string $alternateNameValue, File $file, int $line, int $expectedMaxLength): void
    {
        $this->invalidAlternateNamesValues[] = sprintf(
            '"%s" (max length %d, %d given) -> %s:%d',
            $alternateNameValue,
            $expectedMaxLength,
            strlen($alternateNameValue),
            $file->getPath(),
            $line
        );
    }

    /**
     * Gets the invalid cc2 values.
     *
     * @return array<int, string>
     */
    public function getInvalidCc2Values(): array
    {
        return $this->invalidCc2Values;
    }

    /**
     * Adds an invalid ascii name value.
     *
     * @param string $cc2Value
     * @param File $file
     * @param int $line
     * @param int $expectedMaxLength
     * @return void
     */
    public function addInvalidCc2Values(string $cc2Value, File $file, int $line, int $expectedMaxLength): void
    {
        $this->invalidCc2Values[] = sprintf(
            '"%s" (max length %d, %d given) -> %s:%d',
            $cc2Value,
            $expectedMaxLength,
            strlen($cc2Value),
            $file->getPath(),
            $line
        );
    }
}
