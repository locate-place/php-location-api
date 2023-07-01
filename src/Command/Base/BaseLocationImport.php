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

namespace App\Command\Base;

use DateTimeImmutable;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpContainer\Encoding;
use Ixnode\PhpContainer\File;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\File\FileNotWriteableException;
use Ixnode\PhpException\Type\TypeInvalidException;
use RegexIterator;
use SplFileObject;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class BaseLocationImport
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-28)
 * @since 0.1.0 (2023-06-28) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class BaseLocationImport extends Base
{
    protected const TEXT_ERROR_UNEXPECTED_COUNTS = 'The given number of fields (%d) in row does not match with number of fields (%d) in header.';

    protected const NAME_ARGUMENT_CSV = 'csv';

    private int $splitLines = 100000;

    private const DATE_TRANSLATE = [
        '00.00.0000' => '01.01.1970',
        '31.12.9999' => '31.12.2099',
    ];

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
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
     */
    abstract protected function translateField(bool|string|int $value, string $indexName): bool|string|int|float|null|DateTimeImmutable|array;

    /**
     * Returns the converted row.
     *
     * @param array<int, string> $row
     * @param array<int, string|null> $header
     * @return array<string, mixed>|null
     */
    abstract protected function getDataRow(array $row, array $header, File $csv, int $line): ?array;

    /**
     * Configures the command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument(self::NAME_ARGUMENT_CSV, InputArgument::REQUIRED, 'The path to the CSV file to import.')
        ;
    }

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
     * @throws ClassInvalidException
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

//    /**
//     * Clears the table from given entity.
//     *
//     * @param class-string $entityName
//     * @return bool
//     */
//    protected function clearTable(string $entityName): bool
//    {
//        $this->entity->getEntityManager()->createQuery(sprintf('DELETE %s p', $entityName))->execute();
//
//        $this->printAndLog(sprintf('table %s is truncated.', $entityName));
//
//        return true;
//    }

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
     * Prints invalid row to screen.
     *
     * @param array<string, mixed> $dataRow
     * @param int $line
     * @param string $message
     * @return void
     * @throws TypeInvalidException
     */
    protected function printInvalidRow(array $dataRow, int $line, string $message): void
    {
        $this->printAndLog('--- Invalid row ---');
        $this->printAndLog(sprintf('  - line:    %d', $line));
        $this->printAndLog(sprintf('  - message: %s', $message));
        $this->printAndLog('  - data:');
        foreach ($dataRow as $key => $value) {
            $this->printAndLog(sprintf('    - %-20s%s', $key.':', (new TypeCastingHelper($value))->strval()));
        }
        $this->printAndLog('--- Invalid row ---');
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
}
