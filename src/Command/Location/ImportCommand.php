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
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpContainer\File;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpTimezone\Timezone as IxnodeTimezone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class ImportCommand (Location).
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

    protected bool $checkCommandExecution = false;

    /**
     * Configures the command.
     *
     * @inheritdoc
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName(strval(self::$defaultName))
            ->setDescription('Imports locations from given file.')
            ->setHelp(
                <<<'EOT'

The <info>import:location</info> command imports locations from a given file.

EOT
            );
    }

    /**
     * Returns the field translations.
     *
     * @inheritdoc
     *
     * geonameid         : integer id of record in geonames database
     * name              : name of geographical point (utf8) varchar(200)
     * asciiname         : name of geographical point in plain ascii characters, varchar(200)
     * alternatenames    : alternatenames, comma separated, ascii names automatically transliterated, convenience attribute from alternatename table, varchar(10000)
     * latitude          : latitude in decimal degrees (wgs84)
     * longitude         : longitude in decimal degrees (wgs84)
     * feature class     : see http://www.geonames.org/export/codes.html, char(1)
     * feature code      : see http://www.geonames.org/export/codes.html, varchar(10)
     * country code      : ISO-3166 2-letter country code, 2 characters
     * cc2               : alternate country codes, comma separated, ISO-3166 2-letter country code, 200 characters
     * admin1 code       : fipscode (subject to change to iso code), see exceptions below, see file admin1Codes.txt for display names of this code; varchar(20)
     * admin2 code       : code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80)
     * admin3 code       : code for third level administrative division, varchar(20)
     * admin4 code       : code for fourth level administrative division, varchar(20)
     * population        : bigint (8 byte int)
     * elevation         : in meters, integer
     * dem               : digital elevation model, srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or 30''x30'' (ca 900mx900m) area in meters, integer. srtm processed by cgiar/ciat.
     * timezone          : the iana timezone id (see file timeZone.txt) varchar(40)
     * modification date : date of last modification in yyyy-MM-dd format
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
     * @inheritdoc
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
     * Returns if the given import file has a header row.
     *
     * @inheritdoc
     */
    protected function hasFileHasHeader(): bool
    {
        return false;
    }

    /**
     * Returns the header to be added to split files.
     *
     * @inheritdoc
     */
    protected function getAddHeaderFields(): array|null
    {
        return [
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
        ];
    }

    /**
     * Returns the header separator.
     *
     * @inheritdoc
     */
    protected function getAddHeaderSeparator(): string
    {
        return "\t";
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
            ->setMappingRiverIgnore(false)
        ;

        $this->entityManager->persist($location);

        return $location;
    }

    /**
     * Saves the data as entities.
     *
     * @inheritdoc
     */
    protected function saveEntities(array $data, File $file): int
    {
        $writtenRows = 0;

        /* Update or create entities. */
        foreach ($data as $row) {
            $geonameIdValue = (new TypeCastingHelper($row[self::FIELD_GEONAME_ID]))->intval();
            $nameValue = (new TypeCastingHelper($row[self::FIELD_NAME]))->strval();
            $asciiNameValue = (new TypeCastingHelper($row[self::FIELD_ASCII_NAME]))->strval();
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
     * @param File $file
     * @return bool
     * @throws NonUniqueResultException
     * @throws TypeInvalidException
     */
    protected function hasImportByCountryCodeAndPath(string $countryCode, File $file): bool
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

        return $repository->getNumberOfImports($country, $file) > 0;
    }

    /**
     * Returns the country code from given file.
     *
     * @param File $file
     * @return string
     */
    protected function getCountryCode(File $file): string
    {
        return basename($file->getPath(), '.txt');
    }

    /**
     * Executes a pre check.
     *
     * @inheritdoc
     */
    protected function executePreCheck(string $countryCode, File $file): int
    {
        if (!$this->force && $this->hasImportByCountryCodeAndPath($countryCode, $file)) {
            $this->printAndLog(sprintf('The given country code "%s" was already imported. Use --force to ignore.', $countryCode));
            return Command::INVALID;
        }

        return Command::SUCCESS;
    }

    /**
     * Executes a check.
     *
     * @inheritdoc
     * @throws TypeInvalidException
     */
    protected function executeCheck(): int
    {
        if ($this->checkCommandExecution) {
            return Command::SUCCESS;
        }

        $returnValue = $this->executeCheckCommand();

        if ($returnValue !== Command::SUCCESS) {
            return $returnValue;
        }

        return Command::SUCCESS;
    }

    /**
     * Do get export
     *
     * @inheritdoc
     * @throws ArrayKeyNotFoundException
     */
    protected function doGetExport(File $file): void
    {
        if ($this->checkCommandExecution) {
            return;
        }

        $this->import = $this->getImport($file);
    }

    /**
     * Do after tasks.
     *
     * @inheritdoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function doAfterTask(): void
    {
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
    }

    /**
     * Do update import entity tasks.
     *
     * @inheritdoc
     */
    protected function doUpdateImportEntity(): void
    {
        /* Set last date to Import entity. */
        $this->updateImportEntity();
    }

    /**
     * Returns the unknown timezones.
     *
     * @return array<string, array<int, string>>
     */
    private function getUnknownTimezones(): array
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
    protected function addUnknownTimezones(string $timezone, File $file, int $line): void
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
    private function getInvalidTimezones(): array
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
    protected function addInvalidTimezones(string $timezone, File $file, int $line): void
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
    private function getInvalidNameValues(): array
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
    protected function addInvalidNameValues(string $nameValue, File $file, int $line, int $expectedMaxLength): void
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
    private function getInvalidAsciiNameValues(): array
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
    protected function addInvalidAsciiNameValues(string $asciiNameValue, File $file, int $line, int $expectedMaxLength): void
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
    private function getInvalidAlternateNamesValues(): array
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
    protected function addInvalidAlternateNamesValues(string $alternateNameValue, File $file, int $line, int $expectedMaxLength): void
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
    private function getInvalidCc2Values(): array
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
    protected function addInvalidCc2Values(string $cc2Value, File $file, int $line, int $expectedMaxLength): void
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
