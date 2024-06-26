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

namespace App\Command\Base;

use App\Command\AlternateName\ImportCommand as AlternateNameImportCommand;
use App\Command\Location\ImportCommand as LocationImportCommand;
use App\Command\ZipCode\ImportCommand as ZipCodeImportCommand;
use App\Constants\Code\Encoding;
use App\Constants\Key\KeyCamelCase;
use App\Entity\AlternateName;
use App\Entity\Country;
use App\Entity\Import;
use App\Entity\Location;
use App\Entity\ZipCode;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpContainer\File;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\File\FileNotWriteableException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpTimezone\Country as IxnodeCountry;
use LogicException;
use RegexIterator;
use SplFileObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * Class BaseLocationImport
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-28)
 * @since 0.1.0 (2023-06-28) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class BaseLocationImport extends Base
{
    protected int $splitLines = 10000;

    private const DATE_TRANSLATE = [
        '00.00.0000' => '01.01.1970',
        '31.12.9999' => '31.12.2099',
    ];

    protected const TEXT_WARNING_IGNORED_LINE = 'Ignored line: %s:%d';

    protected const TEXT_IMPORT_START = 'Start importing "%s" - [%d/%d]. Please wait.';

    protected const TEXT_ROWS_WRITTEN = '%d rows written to table %s (%d checked): %.2fs';

    protected const OPTION_NAME_FORCE = 'force';

    protected const DEFAULT_SPLIT_LINES = 10000;

    protected Import $import;

    protected int $ignoredLines = 0;

    /** @var array<int, string> $ignoredTextLines */
    protected array $ignoredTextLines = [];

    protected int $importedRows = 0;

    protected bool $errorFound = false;

    protected float $timeStart;

    /** @var array<string, Country> $countries */
    private array $countries = [];

    protected bool $force = false;

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
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('file', InputArgument::REQUIRED, 'The file to be imported.'),
            ])
            ->addOption(self::OPTION_NAME_FORCE, 'f', InputOption::VALUE_NONE, 'Forces the import even if an import with the same given country code already exists.')
        ;
    }

    /**
     * Returns the import path.
     *
     * @param File $file
     * @return string
     */
    protected function getPathImport(File $file): string
    {
        return $file->getDirectoryPath();
    }

    /**
     * Returns the field translations.
     *
     * @return array<string, string|null>
     */
    abstract protected function getFieldTranslation(): array;

    /**
     * Returns some manually csv rows.
     *
     * @return array<int, array<string, string|bool>>
     */
    abstract protected function getExtraCsvRows(): array;

    /**
     * Translate given value according to given index name.
     *
     * @param bool|string|int $value
     * @param string $indexName
     * @return bool|string|int|float|DateTimeImmutable|array<int, mixed>|null
     * @throws TypeInvalidException
     */
    abstract protected function translateField(bool|string|int $value, string $indexName): bool|string|int|float|null|DateTimeImmutable|array;

    /**
     * Returns if the given import file has a header row.
     *
     * @return bool
     */
    abstract protected function hasFileHasHeader(): bool;

    /**
     * Returns the header to be added to split files.
     *
     * @return array<int, string>|null
     */
    abstract protected function getAddHeaderFields(): array|null;

    /**
     * Returns the header separator.
     *
     * @return string
     */
    abstract protected function getAddHeaderSeparator(): string;

    /**
     * Returns the converted row.
     *
     * @param array<int, string> $row
     * @param array<int, string|null> $header
     * @return array<string, mixed>|null
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
     * Sets the update_at field of Import entity.
     *
     * @return void
     */
    protected function updateImportEntity(): void
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
     * @param File $file
     * @return int
     * @throws TypeInvalidException
     * @throws Exception
     */
    abstract protected function saveEntities(array $data, File $file): int;

    /**
     * Replaces the perl encoding to html entity.
     *
     * @param string $string
     * @return string
     * @throws TypeInvalidException
     */
    protected function replacePerlEncoding(string $string): string
    {
        $replaced = preg_replace('~\\\\x\\{([0-9a-f]+)\\}~', '&#x$1;', $string);

        if (!is_string($replaced)) {
            throw new TypeInvalidException('string', gettype($replaced));
        }

        return $replaced;
    }

    /**
     * Do a html entity encoding.
     *
     * @param string $string
     * @return string
     * @throws TypeInvalidException
     */
    protected function htmlEntityDecode(string $string): string
    {
        return html_entity_decode($this->replacePerlEncoding($string));
    }

    /**
     * Trims the given value and convert to string.
     *
     * @param mixed $value
     * @return string
     * @throws TypeInvalidException
     */
    protected function trimString(mixed $value): string
    {
        return trim((new TypeCastingHelper($value))->strval());
    }

    /**
     * Trims the given value and convert to string or null if empty.
     *
     * @param mixed $value
     * @return string|null
     * @throws TypeInvalidException
     */
    protected function trimStringNull(mixed $value): string|null
    {
        $value = trim((new TypeCastingHelper($value))->strval());

        if (empty($value)) {
            return null;
        }

        return $value;
    }

    /**
     * Trims the given value and convert to integer.
     *
     * @param mixed $value
     * @return int
     * @throws TypeInvalidException
     */
    protected function trimInteger(mixed $value): int
    {
        return intval(trim((new TypeCastingHelper($value))->strval()));
    }

    /**
     * Trims the given value and convert to integer or null if empty.
     *
     * @param mixed $value
     * @return int|null
     * @throws TypeInvalidException
     */
    protected function trimIntegerNull(mixed $value): int|null
    {
        $value = trim((new TypeCastingHelper($value))->strval());

        if (empty($value)) {
            return null;
        }

        return intval($value);
    }

    /**
     * Trims the given value and convert to bool.
     *
     * @param mixed $value
     * @return bool
     * @throws TypeInvalidException
     */
    protected function trimBool(mixed $value): bool
    {
        return intval(trim((new TypeCastingHelper($value))->strval())) === 1;
    }

    /**
     * Trims the given value and convert to float.
     *
     * @param mixed $value
     * @return float
     * @throws TypeInvalidException
     */
    protected function trimFloat(mixed $value): float
    {
        return floatval(str_replace(',', '.', $this->trimString($value)));
    }

    /**
     * Gets X: X -> true, empty string -> false
     *
     * @param mixed $value
     * @param string $compare
     * @return bool
     * @throws TypeInvalidException
     */
    protected function isX(mixed $value, string $compare = 'X'): bool
    {
        $value = $this->trimString($value);

        return $value === $compare;
    }

    /**
     * Returns comma separated list of given string.
     *
     * @param mixed $value
     * @return array<int, string>
     * @throws TypeInvalidException
     */
    protected function splitByComma(mixed $value): array
    {
        $value = $this->trimString($value);

        if (empty($value)) {
            return [];
        }

        return explode(',', $value);
    }

    /**
     * Returns string or null.
     *
     * @param mixed $value
     * @return ?string
     * @throws TypeInvalidException
     */
    protected function getStringNull(mixed $value): ?string
    {
        $value = trim((new TypeCastingHelper($value))->strval());

        if (!empty($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Returns lower case from given string.
     *
     * @param mixed $value
     * @return string
     * @throws TypeInvalidException
     */
    protected function getLowerCase(mixed $value): string
    {
        return strtolower((new TypeCastingHelper($value))->strval());
    }

    /**
     * Converts the first character of each word to uppercase.
     *
     * @param string $value
     * @return string
     */
    protected function getUcWords(string $value): string
    {
        return ucwords(strtolower($value));
    }

    /**
     * Converts a datetime immutable object from given date.
     *
     * @param string $date
     * @param string $time
     * @return DateTimeImmutable|null
     * @throws ClassInvalidException
     */
    protected function getDateTimeImmutable(string $date, string $time = '00:00:00'): ?DateTimeImmutable
    {
        if (empty($date)) {
            return null;
        }

        foreach (self::DATE_TRANSLATE as $dateSource => $dateTarget) {
            if ($date === $dateSource) {
                $date = $dateTarget;
            }
        }

        if ($date === '00.00.0000') {
            $date = '01.01.1970';
        }

        if ($date === '31.12.9999') {
            $date = '31.12.2099';
        }

        $dateTimeImmutable = date_create_immutable_from_format('d.m.Y H:i:s', sprintf('%s %s', $date, $time));

        if (!$dateTimeImmutable instanceof DateTimeImmutable) {
            throw new ClassInvalidException(DateTimeImmutable::class, DateTimeImmutable::class);
        }

        return $dateTimeImmutable;
    }

    /**
     * Removes the BOM from given string.
     *
     * @param string $string
     * @return string
     */
    protected function removeBomUtf8(string $string): string
    {
        if (str_starts_with($string, chr(intval(hexdec('EF'))).chr(intval(hexdec('BB'))).chr(intval(hexdec('BF'))))) {
            return substr($string,3);
        }

        return $string;
    }

    /**
     * Removes the BOM from given row. True if one was removed.
     *
     * @param array<int, string> $row
     * @return bool
     */
    private function removeBomUtf8Row(array &$row): bool
    {
        $lengthBefore = strlen($row[0]);

        $row[0] = $this->removeBomUtf8($row[0]);

        return $lengthBefore > strlen($row[0]);
    }

    /**
     * Ignore unknown translation fields.
     *
     * @return bool
     */
    protected function ignoreUnknownTranslationFields(): bool
    {
        return false;
    }

    /**
     * Returns the translation name for unknown fields.
     *
     * @param string $name
     * @return string
     */
    protected function getTranslationName(string $name): string
    {
        return str_replace(' ', '_', strtolower($name));
    }

    /**
     * Returns the header from given row.
     *
     * @param array<int, string> $row
     * @return array<int, string|null>
     * @throws ArrayKeyNotFoundException
     */
    protected function getHeader(array $row): array
    {
        $fieldTranslation = $this->getFieldTranslation();

        $header = [];

        foreach ($row as $name) {
            if (array_key_exists($name, $fieldTranslation)) {
                $header[] = $fieldTranslation[$name];
                continue;
            }

            if ($this->ignoreUnknownTranslationFields()) {
                $header[] = $this->getTranslationName($name);
                continue;
            }

            throw new ArrayKeyNotFoundException($name);
        }

        return $header;
    }

    /**
     * Reads the given csv file.
     *
     * @param File $csv
     * @param string $separator
     * @return array<int, array<string, mixed>>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function readDataFromCsv(File $csv, string $separator = ';'): array
    {
        $dataCsv = [];

        $path = $csv->getPath();

        $handle = fopen($csv->getPath(), 'r');

        if ($handle === false) {
            throw new FileNotReadableException($path);
        }

        $encoding = $csv->getEncoding();

        $this->printAndLog(sprintf('Detected encoding (%s): %s', $path, $encoding));

        $header = [];
        $rowIndex = 0;
        $currentLine = 0;
        while (($row = fgetcsv($handle, 16384, $separator)) !== false) {

            /* Remove bom if it exists. */
            if ($rowIndex === 0) {
                $this->removeBomUtf8Row($row);
            }

            /* Encode encoding if necessary. Utf8 is needed for tables. */
            $row = match ($encoding) {
                Encoding::ASCII, Encoding::ISO_8859_1 => array_map('utf8_encode', $row),
                Encoding::UTF_8 => $row,
                default => throw new TypeInvalidException('string', strval($encoding)),
            };

            if ($rowIndex === 0) {
                $header = $this->getHeader($row);
                $rowIndex++;
                $currentLine++;
                continue;
            }

            $dataRow = $this->getDataRow($row, $header, $csv, $currentLine + 1);

            if (is_null($dataRow)) {
                $currentLine++;
                continue;
            }

            $dataCsv[] = $dataRow;

            $rowIndex++;
            $currentLine++;
        }

        fclose($handle);

        foreach ($this->getExtraCsvRows() as $csvRow) {
            foreach ($csvRow as $indexName => $value) {
                /** @phpstan-ignore-next-line */
                if (!(is_bool($value) || is_string($value) || is_int($value))) {
                    throw new CaseInvalidException(
                        'not bool or string or int',
                        ['bool', 'string', 'int']
                    );
                }

                $csvRow[$indexName] = $this->translateField($value, $indexName);
            }

            $dataCsv[] = $csvRow;
        }

        return $dataCsv;
    }

    /**
     * Clears the tmp folder.
     *
     * @param File $file
     * @param string|string[] $path
     * @return void
     * @throws TypeInvalidException
     */
    protected function clearTmpFolder(File $file, string|array $path): void
    {
        $counterUnlinked = 0;
        foreach ($this->getFilesTmp($file, $path) as $fileTmp) {
            if (is_file($fileTmp)) {
                unlink($fileTmp);
                $counterUnlinked++;
            }
        }

        if (is_array($path)) {
            $path = implode('/', $path);
        }

        $this->printAndLog(sprintf('%d files were deleted for "%s".', $counterUnlinked, $path));
    }

    /**
     * Returns the existing files within the given path.
     *
     * @param File $file
     * @param string|string[] $path
     * @return array<int, string>
     * @throws TypeInvalidException
     */
    protected function getFilesTmp(File $file, string|array $path): array
    {
        if (is_array($path)) {
            $path = implode('/', $path);
        }

        $pathComplete = sprintf('%s/%s/%s', $this->getPathImport($file), File::PATH_TEMP, $path);

        $files = glob(sprintf('%s/*', $pathComplete));

        if ($files === false) {
            throw new TypeInvalidException('array', 'bool');
        }

        return $files;
    }

    /**
     * Returns the CSV File from given argument.
     *
     * @param string $argumentName
     * @return File|null
     * @throws TypeInvalidException
     */
    protected function getCsvFile(string $argumentName): ?File
    {
        $path = (new TypeCastingHelper($this->input->getArgument($argumentName)))->strval();

        $file = new File($path);

        if (!$file->exist()) {
            return null;
        }

        return $file;
    }

    /**
     * Creates the split path.
     *
     * @param File $file
     * @param string|array<int, string> $path
     * @return void
     * @throws CaseInvalidException
     * @throws TypeInvalidException
     * @throws FileNotWriteableException
     */
    private function createSplitPath(File $file, string|array $path): void
    {
        if (is_array($path)) {
            $path = implode('/', $path);
        }

        $pathSplit = dirname($file->getPathNumberedWithTmp(1, sprintf('%s/%s', File::PATH_TEMP, $path)));

        /* The split path exists, but is not a directory. */
        if (file_exists($pathSplit) && !is_dir($pathSplit)) {
            throw new CaseInvalidException('file', ['directory']);
        }

        /* Create export path */
        if (!file_exists($pathSplit)) {
            mkdir($pathSplit, 0775, true);
        }

        if (!file_exists($pathSplit)) {
            throw new FileNotWriteableException($pathSplit);
        }

        if (!is_writeable($pathSplit)) {
            throw new FileNotWriteableException($pathSplit);
        }

        return;
    }

    /**
     * Splits the given file.
     *
     * @param File $file
     * @param string|string[] $path
     * @param bool $fileHasHeader
     * @param array<int, string>|null $addHeaderFields
     * @param string $addHeaderSeparator
     * @return void
     * @throws CaseInvalidException
     * @throws FileNotWriteableException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function splitFile(
        File $file,
        string|array $path,
        bool $fileHasHeader = false,
        ?array $addHeaderFields = null,
        string $addHeaderSeparator = ','
    ): void
    {
        $this->createSplitPath($file, $path);

        if (is_array($path)) {
            $path = implode('/', $path);
        }

        $rowArray = new RegexIterator(new SplFileObject($file->getPath()), '/\n/', RegexIterator::SPLIT);
        $fileNumber = 0;
        $target = null;

        $header = null;

        /* Get header from file */
        if (is_null($header) && $fileHasHeader) {
            $rowArray->next();

            /** @var array<int, string> $row */
            $row = $rowArray->current();
            $header = $row[0];
        }

        /* Add header from given fields. */
        if (is_null($header) && is_array($addHeaderFields)) {
            $header = implode($addHeaderSeparator, $addHeaderFields);
        }

        /* Get rows from source csv */
        $counter = 0;
        $rowArray->next();
        while ($rowArray->current()) {
            if ($counter % $this->getSplitLines() === 0) {
                $fileNumber++;

                $target = new SplFileObject(
                    $file->getPathNumberedWithTmp(
                        $fileNumber,
                        sprintf('%s/%s', File::PATH_TEMP, $path)
                    ),
                    'w'
                );

                /* Add header if given. */
                if (!is_null($header)) {
                    $target->fwrite($header.PHP_EOL);
                }
            }

            if (!$target instanceof SplFileObject) {
                throw new TypeInvalidException('object', gettype($target));
            }

            /** @var array<int, string> $row */
            $row = $rowArray->current();

            $target->fwrite($row[0].PHP_EOL);

            $counter++;
            $rowArray->next();
        }

        $this->printAndLog(sprintf('The file "%s" was split into %d files.', $file->getPath(), $fileNumber));
    }

    /**
     * @return int
     */
    public function getSplitLines(): int
    {
        return $this->splitLines;
    }

    /**
     * @param int $splitLines
     * @return self
     */
    public function setSplitLines(int $splitLines): self
    {
        $this->splitLines = $splitLines;

        return $this;
    }

    /**
     * Returns the number of ignored lines.
     *
     * @return int
     */
    protected function getIgnoredLines(): int
    {
        return $this->ignoredLines;
    }

    /**
     * Returns the ignored text lines.
     *
     * @return array<int, string>
     */
    protected function getIgnoredTextLines(): array
    {
        return $this->ignoredTextLines;
    }

    /**
     * Add ignored line to log.
     *
     * @param File $csv
     * @param int $line
     * @return void
     */
    protected function addIgnoredLine(File $csv, int $line): void
    {
        $this->ignoredLines++;
        $this->ignoredTextLines[] = sprintf('%s:%d', $csv->getPath(), $line);
        $this->printAndLog(sprintf(
            self::TEXT_WARNING_IGNORED_LINE,
            $csv->getPath(),
            $line
        ));
    }

    /**
     * Extracts the table name from the current import class.
     *
     * @return string
     */
    private function getTableName(): string
    {
        return match (static::class) {
            AlternateNameImportCommand::class => 'alternate_name',
            LocationImportCommand::class => 'location',
            ZipCodeImportCommand::class => 'zip_code',
            default => throw new LogicException(sprintf('Unsupported import class "%s".', static::class)),
        };
    }

    /**
     * Extracts the entity name from the current import class.
     *
     * @return string
     */
    private function getEntityName(): string
    {
        return match (static::class) {
            AlternateNameImportCommand::class => AlternateName::class,
            LocationImportCommand::class => Location::class,
            ZipCodeImportCommand::class => ZipCode::class,
            default => throw new LogicException(sprintf('Unsupported import class "%s".', static::class)),
        };
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
        $this->printAndLog(sprintf('Start writing "%s" entities.', $this->getEntityName()));
        $timeStart = microtime(true);
        $rows = $this->saveEntities($data, $file);
        $timeExecution = (microtime(true) - $timeStart);
        $this->printAndLog(sprintf(
            self::TEXT_ROWS_WRITTEN,
            $rows,
            $this->getTableName(),
            count($data),
            $timeExecution
        ));

        $this->importedRows += $rows;
    }

    /**
     * Start timer.
     *
     * @return void
     */
    protected function startTimer(): void
    {
        $this->timeStart = microtime(true);
    }

    /**
     * Returns the country code from given file.
     *
     * @param File $file
     * @return string
     */
    abstract protected function getCountryCode(File $file): string;

    /**
     * Executes a pre check.
     *
     * @param string $countryCode
     * @param File $file
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function executePreCheck(string $countryCode, File $file): int
    {
        return Command::SUCCESS;
    }

    /**
     * Executes a check.
     *
     * @return int
     */
    protected function executeCheck(): int
    {
        return Command::SUCCESS;
    }

    /**
     * Do get export
     *
     * @param File $file
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function doGetExport(File $file): void
    {
    }

    /**
     * Do after tasks.
     *
     * @return void
     */
    protected function doAfterTask(): void
    {
    }

    /**
     * Do update import entity tasks.
     *
     * @return void
     */
    protected function doUpdateImportEntity(): void
    {
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->startTimer();

        $this->output = $output;
        $this->input = $input;

        $this->force = $input->hasOption(self::OPTION_NAME_FORCE) && (bool) $input->getOption(self::OPTION_NAME_FORCE);

        $file = $this->getCsvFile('file');

        if (is_null($file)) {
            $this->printAndLog(sprintf('The given CSV file for "%s" does not exist.', $file));
            return Command::INVALID;
        }

        $countryCode = $this->getCountryCode($file);

        $preCheck = $this->executePreCheck($countryCode, $file);

        if ($preCheck !== Command::SUCCESS) {
            return $preCheck;
        }

        $type = sprintf('%s/csv-import', $countryCode);

        $this->createLogInstanceFromFile($file, $type);

        $check = $this->executeCheck();

        if ($check !== Command::SUCCESS) {
            return $preCheck;
        }

        $this->clearTmpFolder($file, $countryCode);
        $this->setSplitLines(self::DEFAULT_SPLIT_LINES);
        $this->splitFile(
            $file,
            $countryCode,
            $this->hasFileHasHeader(),
            $this->getAddHeaderFields(),
            $this->getAddHeaderSeparator()
        );

        $this->doGetExport($file);

        /* Get tmp files */
        $splitFiles = $this->getFilesTmp($file, $countryCode);

        /* Execute all split files */
        foreach ($splitFiles as $index => $splitFile) {
            $this->doExecute(new File($splitFile), $index + 1, count($splitFiles));
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

        $this->doAfterTask();

        if (!$this->errorFound) {
            $this->printAndLog('---');
            $this->printAndLog('Finish. No error was found.');
        }

        $this->doUpdateImportEntity();

        /* Command successfully executed. */
        return Command::SUCCESS;
    }
}
