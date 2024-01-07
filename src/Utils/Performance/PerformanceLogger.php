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

namespace App\Utils\Performance;

use App\Constants\Key\KeyArray;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use LogicException;

/**
 * Class PerformanceLogger
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */
class PerformanceLogger
{
    private const GROUP_DEFAULT = 'default';

    private const BYTES_PER_MB = 1024 * 1024;

    private const MILLISECONDS_PER_SECOND = 1000;

    private static self|null $instance = null;

    private readonly Json $logData;

    /** @var array<string, array<string, float>> $startTime */
    private array $startTime = [];

    /**
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     * @throws JsonException
     */
    private function __construct()
    {
        $this->logData = new Json([]);
    }

    /**
     * Returns the singleton instance.
     *
     * @return PerformanceLogger
     */
    public static function getInstance(): PerformanceLogger
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Calls the given callable and logs its performance data.
     *
     * @param callable $callable
     * @param string $name
     * @param string $group
     * @param array<string, string|int> $additionalLog
     * @return void
     * @throws FunctionReplaceException
     * @throws TypeInvalidException
     */
    public function logPerformance(callable $callable, string $name, string $group = self::GROUP_DEFAULT, array $additionalLog = null): void
    {
        /* Start logging performance data. */
        $this->startLogPerformance($name, $group);

        /* Execute the callable. */
        $callable();

        /* Finish logging performance data. */
        $this->endLogPerformance($name, $group, $additionalLog);
    }

    /**
     * Returns the performance data.
     *
     * @return Json
     */
    public function getPerformanceData(): Json
    {
        return $this->logData;
    }

    /**
     * Returns the memory usage in MB.
     *
     * @return float
     */
    public function getMemoryUsed(): float
    {
        return memory_get_usage() / self::BYTES_PER_MB;
    }

    /**
     * Returns the memory usage in MB formatted.
     *
     * @param float|null $memoryUsed
     * @return string
     */
    public function getMemoryUsedFormatted(float $memoryUsed = null): string
    {
        $memoryUsed = is_null($memoryUsed) ? $this->getMemoryUsed() : $memoryUsed;

        return sprintf('%.2f MB', $memoryUsed);
    }

    /**
     * Returns the additional data according to the given parameters.
     *
     * @param string $className
     * @param string|null $methodName
     * @param int|null $line
     * @return array<string, string|int>
     */
    public function getAdditionalData(string $className, string $methodName = null, int $line = null): array
    {
        $additionalData = [
            KeyArray::CLASS_NAME => $className,
        ];

        if (!is_null($methodName)) {
            $additionalData[KeyArray::METHOD] = $methodName;
        }

        if (!is_null($line)) {
            $additionalData[KeyArray::LINE] = $line;
        }

        return $additionalData;
    }

    /**
     * Returns the group name from the given file name.
     *
     * @param string $file
     * @return string
     */
    public function getGroupNameFromFileName(string $file): string
    {
        $baseName = basename($file);

        return pathinfo($baseName, PATHINFO_FILENAME);
    }

    /**
     * Returns the formatted time.
     *
     * @param float $time
     * @return string
     */
    private function getTimeFormatted(float $time): string
    {
        return sprintf('%.2f ms', $time);
    }

    /**
     * Start logging performance data.
     *
     * @param string $name
     * @param string $group
     * @return void
     */
    private function startLogPerformance(string $name, string $group = self::GROUP_DEFAULT): void
    {
        if (!array_key_exists($group, $this->startTime)) {
            $this->startTime[$group] = [];
        }

        if (array_key_exists($name, $this->startTime[$group])) {
            throw new LogicException(sprintf('The group, name combination is already used: %s, %s', $group, $name));
        }

        $this->startTime[$group][$name] = microtime(true);
    }

    /**
     * Finish logging performance data.
     *
     * @param string $name
     * @param string $group
     * @param array<string, string|int>|null $additionalLog
     * @return void
     * @throws FunctionReplaceException
     * @throws TypeInvalidException
     */
    private function endLogPerformance(string $name, string $group = self::GROUP_DEFAULT, array $additionalLog = null): void
    {
        if (!array_key_exists($group, $this->startTime) || !array_key_exists($name, $this->startTime[$group])) {
            throw new LogicException('Use PerformanceLogger::startLogPerformance() before calling this method');
        }

        $time = (microtime(true) - $this->startTime[$group][$name]) * self::MILLISECONDS_PER_SECOND;
        $memoryUsed = $this->getMemoryUsed();

        $this->logData->addValue([$group, $name, 'time'], [
            KeyArray::VALUE => round($time, 2),
            KeyArray::VALUE_FORMATTED => $this->getTimeFormatted($time),
        ]);
        $this->logData->addValue([$group, $name, 'memory'], [
            KeyArray::VALUE => round($memoryUsed, 2),
            KeyArray::VALUE_FORMATTED => $this->getMemoryUsedFormatted($memoryUsed),
        ]);

        if (!is_null($additionalLog)) {
            $this->logData->addValue([$group, $name, 'additional'], $additionalLog);
        }

        unset($this->startTime[$group][$name]);
    }
}
