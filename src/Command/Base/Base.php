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

use DateTimeImmutable;
use Ixnode\PhpContainer\File;
use Ixnode\PhpException\File\FileNotWriteableException;
use SplFileObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BaseImport
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 */
abstract class Base extends Command
{
    protected InputInterface $input;

    protected OutputInterface $output;

    protected SplFileObject $fileLog;

    /**
     * Returns the log file path from given File object.
     *
     * @param File $file
     * @param string $type
     * @return string
     * @throws FileNotWriteableException
     */
    protected function getFileLogFromFile(File $file, string $type = 'csv-import'): string
    {
        $pathLog = sprintf(
            '%s/log/%s.%s.log',
            $file->getDirectoryPath(),
            $type,
            (new DateTimeImmutable())->format('Ymd-His')
        );

        $directoryLog = dirname($pathLog);

        if (!is_dir($directoryLog)) {
            mkdir($directoryLog, 0775, true);
        }

        if (!is_dir($directoryLog)) {
            throw new FileNotWriteableException($directoryLog);
        }

        return $pathLog;
    }

    /**
     * Returns the log path.
     *
     * @param string $directoryPath
     * @param string $type
     * @return string
     * @throws FileNotWriteableException
     */
    protected function getFileLog(string $directoryPath, string $type = 'csv-import'): string
    {
        $pathLog = sprintf(
            '%s/log/%s.%s.log',
            $directoryPath,
            $type,
            (new DateTimeImmutable())->format('Ymd-His')
        );

        $directoryLog = dirname($pathLog);

        if (!is_dir($directoryLog)) {
            mkdir($directoryLog, 0775, true);
        }

        if (!is_dir($directoryLog)) {
            throw new FileNotWriteableException($directoryLog);
        }

        return $pathLog;
    }

    /**
     * Create log instance from given path.
     *
     * @param string $path
     * @param string $type
     * @return bool
     * @throws FileNotWriteableException
     */
    protected function createLogInstance(string $path, string $type): bool
    {
        $fileLog = $this->getFileLog($path, $type);

        $this->fileLog = new SplFileObject($fileLog, 'w');

        return true;
    }

    /**
     * Create log instance from given file object.
     *
     * @param File $file
     * @param string $type
     * @return bool
     * @throws FileNotWriteableException
     */
    protected function createLogInstanceFromFile(File $file, string $type): bool
    {
        $fileLog = $this->getFileLogFromFile($file, $type);

        $this->fileLog = new SplFileObject($fileLog, 'w');

        return true;
    }

    /**
     * Prints and logs given message.
     *
     * @param string $message
     * @return void
     */
    protected function printAndLog(string $message): void
    {
        $message = sprintf('[%s] %s', (new DateTimeImmutable())->format('c'), $message);

        $this->print($message);
        $this->log($message);
    }

    /**
     * Logs given message.
     *
     * @param string $message
     * @return void
     */
    protected function print(string $message): void
    {
        $this->output->writeln($message);
        flush();
    }

    /**
     * Logs given message.
     *
     * @param string $message
     * @return void
     */
    protected function log(string $message): void
    {
        if (!isset($this->fileLog)) {
            return;
        }

        $this->fileLog->fwrite($message.PHP_EOL);
    }
}
