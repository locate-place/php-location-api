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

namespace App\Command\ZipCode;

use App\Command\Base\BaseLocationImport;
use App\Constants\Key\KeyCamelCase;
use App\DBAL\GeoLocation\ValueObject\Point;
use App\Entity\AdminCode;
use App\Entity\Country;
use App\Entity\ZipCode;
use App\Repository\ZipCodeRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpContainer\File;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpTimezone\Country as IxnodeCountry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * Class ImportCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-24)
 * @since 0.1.0 (2024-02-24) First version.
 * @example bin/console zip-code:import [file]
 * @example bin/console zip-code:import import/zip-code/all.txt
 * @download bin/console bin/console zip-code:download
 * @see https://download.geonames.org/export/zip/
 */
class ImportCommand extends BaseLocationImport
{
    protected static $defaultName = 'zip-code:import';

    /** @var array<string, AdminCode> $adminCodes */
    private array $adminCodes = [];

    /** @var array<string, ZipCode> $zipCodes */
    private array $zipCodes = [];

    /** @var array<string, Country> $countries */
    private array $countries = [];

    protected const FIELD_COUNTRY_CODE = 'country-code';

    protected const FIELD_POSTAL_CODE = 'postal-code';

    protected const FIELD_PLACE_NAME = 'place-name';

    protected const FIELD_ADMIN_NAME_1 = 'admin-name-1';

    protected const FIELD_ADMIN_CODE_1 = 'admin-code-1';

    protected const FIELD_ADMIN_NAME_2 = 'admin-name-2';

    protected const FIELD_ADMIN_CODE_2 = 'admin-code-2';

    protected const FIELD_ADMIN_NAME_3 = 'admin-name-3';

    protected const FIELD_ADMIN_CODE_3 = 'admin-code-3';

    protected const FIELD_LATITUDE = 'latitude';

    protected const FIELD_LONGITUDE = 'longitude';

    protected const FIELD_ACCURACY = 'accuracy';

    protected bool $errorFound = false;

    protected float $timeStart;

    protected int $importedRows = 0;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ZipCodeRepository $zipCodeRepository
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ZipCodeRepository $zipCodeRepository,
    )
    {
        parent::__construct();
    }

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
     * @inheritdoc
     *
     * country code      : iso country code, 2 characters
     * postal code       : varchar(20)
     * place name        : varchar(180)
     * admin name1       : 1. order subdivision (state) varchar(100)
     * admin code1       : 1. order subdivision (state) varchar(20)
     * admin name2       : 2. order subdivision (county/province) varchar(100)
     * admin code2       : 2. order subdivision (county/province) varchar(20)
     * admin name3       : 3. order subdivision (community) varchar(100)
     * admin code3       : 3. order subdivision (community) varchar(20)
     * latitude          : estimated latitude (wgs84)
     * longitude         : estimated longitude (wgs84)
     * accuracy          : accuracy of lat/lng from 1=estimated, 4=geonameid, 6=centroid of addresses or shape
     */
    protected function getFieldTranslation(): array
    {
        return [
            'CountryCode' => self::FIELD_COUNTRY_CODE,
            'PostalCode' => self::FIELD_POSTAL_CODE,
            'PlaceName' => self::FIELD_PLACE_NAME,
            'AdminName1' => self::FIELD_ADMIN_NAME_1,
            'AdminCode1' => self::FIELD_ADMIN_CODE_1,
            'AdminName2' => self::FIELD_ADMIN_NAME_2,
            'AdminCode2' => self::FIELD_ADMIN_CODE_2,
            'AdminName3' => self::FIELD_ADMIN_NAME_3,
            'AdminCode3' => self::FIELD_ADMIN_CODE_3,
            'Latitude' => self::FIELD_LATITUDE,
            'Longitude' => self::FIELD_LONGITUDE,
            'Accuracy' => self::FIELD_ACCURACY,
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
            self::FIELD_COUNTRY_CODE,
            self::FIELD_POSTAL_CODE,
            self::FIELD_PLACE_NAME,
            self::FIELD_ADMIN_CODE_1,
            self::FIELD_ADMIN_CODE_2,
            self::FIELD_ADMIN_CODE_3 => $this->trimString($value),

            self::FIELD_ADMIN_NAME_1,
            self::FIELD_ADMIN_NAME_2,
            self::FIELD_ADMIN_NAME_3 => $this->trimStringNull($value),

            self::FIELD_LATITUDE,
            self::FIELD_LONGITUDE => $this->trimFloat($value),

            self::FIELD_ACCURACY => $this->trimIntegerNull($value),

            default => $value,
        };
    }

    /**
     * Returns the converted row.
     *
     * @inheritdoc
     * @throws TypeInvalidException
     */
    protected function getDataRow(array $row, array $header, File $csv, int $line): ?array
    {
        if (count($row) !== count($header)) {
            $this->addIgnoredLine($csv, $line);
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
     * Returns an existing AdminCode entity.
     *
     * @param Country $country
     * @param string|null $adminCode1
     * @param string|null $adminCode2
     * @param string|null $adminCode3
     * @param string|null $adminCode4
     * @param string|null $adminName1
     * @param string|null $adminName2
     * @param string|null $adminName3
     * @return AdminCode|null
     */
    protected function getAdminCode(
        Country $country,
        string|null $adminCode1,
        string|null $adminCode2,
        string|null $adminCode3,
        string|null $adminCode4 = null,
        string|null $adminName1 = null,
        string|null $adminName2 = null,
        string|null $adminName3 = null
    ): ?AdminCode
    {
        $index = sprintf(
            '%s-%s-%s-%s-%s',
            $country->getCode(),
            $adminCode1 ?? 'NULL',
            $adminCode2 ?? 'NULL',
            $adminCode3 ?? 'NULL',
            $adminCode4 ?? 'NULL'
        );

        /* Use cache. */
        if (array_key_exists($index, $this->adminCodes)) {
            return $this->adminCodes[$index];
        }

        $repository = $this->entityManager->getRepository(AdminCode::class);

        $parameter = [
            KeyCamelCase::COUNTRY => $country,
        ];

        /* Ignore admin code 1. */
        $parameter[KeyCamelCase::ADMIN_2_CODE] = $adminCode2;
        $parameter[KeyCamelCase::ADMIN_3_CODE] = $adminCode3;
        $parameter[KeyCamelCase::ADMIN_4_CODE] = $adminCode4;

        $adminCode = $repository->findOneBy($parameter);

        /* Create new entity. */
        if (!$adminCode instanceof AdminCode) {
            return null;
        }

        /* Set admin code 1 (v2). */
        if (!is_null($adminCode1)) {
            $adminCode->setAdmin1Code2($adminCode1);
        }

        /* Set admin names. */
        if (!is_null($adminName1)) {
            $adminCode->setAdmin1Name($adminName1);
        }
        if (!is_null($adminName2)) {
            $adminCode->setAdmin2Name($adminName2);
        }
        if (!is_null($adminName3)) {
            $adminCode->setAdmin3Name($adminName3);
        }

        $this->adminCodes[$index] = $adminCode;

        return $adminCode;
    }

    /**
     * Returns or creates a new AlternateName entity.
     *
     * @param Country $country
     * @param string $postalCode
     * @param string $placeName
     * @param AdminCode $adminCode
     * @param float $latitude
     * @param float $longitude
     * @param int|null $accuracy
     * @return ZipCode|null
     */
    protected function getZipCode(
        Country $country,
        string $postalCode,
        string $placeName,
        AdminCode $adminCode,
        float $latitude,
        float $longitude,
        int|null $accuracy
    ): ZipCode|null
    {
        $index = sprintf(
            '%s-%s-%s-%s',
            $country->getCode(),
            $adminCode->getId(),
            $placeName,
            $postalCode
        );

        /* Use cache. */
        if (array_key_exists($index, $this->zipCodes)) {
            return $this->zipCodes[$index];
        }

        $repository = $this->entityManager->getRepository(ZipCode::class);

        $zipCode = $repository->findOneBy([
            KeyCamelCase::COUNTRY => $country,
            KeyCamelCase::ADMIN_CODE => $adminCode,
            KeyCamelCase::PLACE_NAME => $placeName,
            KeyCamelCase::POSTAL_CODE => $postalCode,
        ]);

        /* Create new entity. */
        if (!$zipCode instanceof ZipCode) {
            $zipCode = (new ZipCode())
                ->setCountry($country)
                ->setAdminCode($adminCode)
                ->setPlaceName($placeName)
                ->setPostalCode($postalCode)
            ;
        }

        $coordinate = new Point($latitude, $longitude);

        /* Update entity. */
        $zipCode
            ->setCoordinate($coordinate)
            ->setAccuracy($accuracy)
        ;

        $this->entityManager->persist($zipCode);

        $this->zipCodes[$index] = $zipCode;

        return $zipCode;
    }

    /**
     * Saves the data as entities.
     *
     * @param array<int, array<string, mixed>> $data
     * @param File $file
     * @return int
     * @throws TypeInvalidException
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function saveEntities(array $data, File $file): int
    {
        $writtenRows = 0;

        /* Update or create entities. */
        foreach ($data as $number => $row) {
            $countryCode = (new TypeCastingHelper($row[self::FIELD_COUNTRY_CODE]))->strval();
            $postalCode = (new TypeCastingHelper($row[self::FIELD_POSTAL_CODE]))->strval();
            $placeName = (new TypeCastingHelper($row[self::FIELD_PLACE_NAME]))->strval();

            $adminName1 = (new TypeCastingHelper($row[self::FIELD_ADMIN_NAME_1]))->strval();
            $adminCode1 = (new TypeCastingHelper($row[self::FIELD_ADMIN_CODE_1]))->strval();
            $adminName2 = (new TypeCastingHelper($row[self::FIELD_ADMIN_NAME_2]))->strval();
            $adminCode2 = (new TypeCastingHelper($row[self::FIELD_ADMIN_CODE_2]))->strval();
            $adminName3 = (new TypeCastingHelper($row[self::FIELD_ADMIN_NAME_3]))->strval();
            $adminCode3 = (new TypeCastingHelper($row[self::FIELD_ADMIN_CODE_3]))->strval();

            $latitude = (new TypeCastingHelper($row[self::FIELD_LATITUDE]))->floatval();
            $longitude = (new TypeCastingHelper($row[self::FIELD_LONGITUDE]))->floatval();
            $accuracy = (new TypeCastingHelper($row[self::FIELD_ACCURACY]))->intval();

            $adminCode1 = empty($adminCode1) ? null : $adminCode1;
            $adminCode2 = empty($adminCode2) ? null : $adminCode2;
            $adminCode3 = empty($adminCode3) ? null : $adminCode3;

            $adminName1 = empty($adminName1) ? null : $adminName1;
            $adminName2 = empty($adminName2) ? null : $adminName2;
            $adminName3 = empty($adminName3) ? null : $adminName3;

            $accuracy = empty($accuracy) ? null : $accuracy;

            $country = $this->getCountry($countryCode);

            $adminCode = $this->getAdminCode(
                country: $country,
                adminCode1: $adminCode1,
                adminCode2: $adminCode2,
                adminCode3: $adminCode3,
                adminName1: $adminName1,
                adminName2: $adminName2,
                adminName3: $adminName3
            );

            if (is_null($adminCode)) {
                $this->printAndLog(sprintf(
                    'Admin code not found: %s %s %s',
                    $country->getCode(),
                    is_null($adminCode2) ? 'NULL' : $adminCode2,
                    is_null($adminCode3) ? 'NULL' : $adminCode3
                ));
                continue;
            }

            $zipCode = $this->getZipCode(
                $country,
                $postalCode,
                $placeName,
                $adminCode,
                $latitude,
                $longitude,
                $accuracy
            );

            if (is_null($zipCode)) {
                $this->addIgnoredLine($file, $number + 1);
                continue;
            }

            $writtenRows++;
        }

        $this->printAndLog('Start flushing ZipCode entities.');
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

        /* Imports the AlternateName data */
        $this->printAndLog('Start writing AlternateName entities.');
        $timeStart = microtime(true);
        $rows = $this->saveEntities($data, $file);
        $timeExecution = (microtime(true) - $timeStart);
        $this->printAndLog(sprintf(
            self::TEXT_ROWS_WRITTEN,
            $rows,
            'zip_code',
            count($data),
            $timeExecution
        ));

        $this->importedRows += $rows;
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

        //$force = $input->hasOption(self::OPTION_NAME_FORCE) && (bool) $input->getOption(self::OPTION_NAME_FORCE);

        $file = $this->getCsvFile('file');

        if (is_null($file)) {
            $this->printAndLog(sprintf('The given CSV file for "%s" does not exist.', $file));
            return Command::INVALID;
        }

        $countryCode = 'ALL';

        $type = sprintf('%s/csv-import', $countryCode);

        $this->createLogInstanceFromFile($file, $type);

        $this->clearTmpFolder($file, $countryCode);
        $this->setSplitLines(10000);
        $this->splitFile(
            $file,
            $countryCode,
            false,
            [
                'CountryCode',  /*  1 */
                'PostalCode',   /*  2 */
                'PlaceName',    /*  3 */
                'AdminName1',   /*  4 */
                'AdminCode1',   /*  5 */
                'AdminName2',   /*  6 */
                'AdminCode2',   /*  7 */
                'AdminName3',   /*  8 */
                'AdminCode3',   /*  9 */
                'Latitude',     /* 10 */
                'Longitude',    /* 11 */
                'Accuracy',     /* 12 */
            ],
            "\t"
        );

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
            foreach ($this->getIgnoredTextLines() as $ignoredLine) {
                $this->printAndLog(sprintf('- %s', $ignoredLine));
            }
            $this->errorFound = true;
        }

        if (!$this->errorFound) {
            $this->printAndLog('---');
            $this->printAndLog('Finish. No error was found.');
        }

        /* Command successfully executed. */
        return Command::SUCCESS;
    }
}
