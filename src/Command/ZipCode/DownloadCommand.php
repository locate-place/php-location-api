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

use App\Command\Base\Base;
use Exception;
use Ixnode\PhpException\File\FileNotWriteableException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use ZipArchive;

/**
 * Class DownloadCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-13)
 * @since 0.1.0 (2024-03-13) First version.
 * @example bin/console zip-code:download
 */
class DownloadCommand extends Base
{
    protected static string $defaultName = 'zip-code:download';

    protected const URL_DOWNLOAD_ZIP_CODE = 'https://download.geonames.org/export/zip/allCountries.zip';

    protected const URL_DOWNLOAD_README = 'https://download.geonames.org/export/zip/readme.txt';

    protected const PATH_IMPORT_ZIP_CODE = 'import/zip-code';

    protected const TEXT_ERROR_UNZIPPED_FILE_NOT_FOUND = 'Unable to find file "%s" from zip archive.';

    protected const TEXT_ERROR_FILE_NOT_FOUND = 'Unable to find file "%s" from download url.';

    protected const TEXT_ERROR_UNZIP = 'Unable to unzip file "%s".';

    protected const TEXT_ERROR_DOWNLOAD_LOCATION = 'Unable to download location file from %s.';

    protected const TEXT_ERROR_URL_PATH_DOES_NOT_EXIST = 'The given url "%s" does not exist.';

    protected const TEXT_INFORMATION_URL_PATH_EXISTS = 'The given url "%s" exists.';

    protected const TEXT_SUCCESS_DOWNLOAD = 'Successfully downloaded zip code files from %s.';

    protected const TEXT_MESSAGE_DOWNLOAD_TRY = 'Try to download zip code from %s.';

    protected const TEXT_MESSAGE_DOWNLOAD = 'Download zip code file from %s to %s.';

    protected const TEXT_MESSAGE_DOWNLOAD_README = 'Download readme file from %s to %s.';

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setName(strval(self::$defaultName))
            ->setDescription('Downloads location from given url.')
            ->setHelp(
                <<<'EOT'

The <info>location:download</info> command downloads locations from a given country code.

EOT
            );
    }

    /**
     * Download zip code file from given url.
     *
     * @param string $url
     * @return bool
     * @throws FileNotWriteableException
     */
    private function downloadLocationFile(string $url): bool
    {
        $pathTxt = sprintf('%s/all.txt', self::PATH_IMPORT_ZIP_CODE);
        $pathReadme = sprintf('%s/all.readme.txt', self::PATH_IMPORT_ZIP_CODE);

        $pathDownload = sprintf('%s/download/all', self::PATH_IMPORT_ZIP_CODE);
        $pathZip = sprintf('%s/all.zip', $pathDownload);
        $pathZipTxt = sprintf('%s/allCountries.txt', $pathDownload);

        if (!is_dir($pathDownload)) {
            mkdir($pathDownload, 0775, true);
        }

        if (!is_dir($pathDownload)) {
            throw new FileNotWriteableException($pathDownload);
        }

        $headers = get_headers($url);

        if ($headers === false) {
            $this->printAndLog(sprintf(self::TEXT_ERROR_URL_PATH_DOES_NOT_EXIST, $url));
            return false;
        }

        if (!str_contains((string) $headers[0], '200')) {
            $this->printAndLog(sprintf(self::TEXT_ERROR_URL_PATH_DOES_NOT_EXIST, $url));
            return false;
        }

        $this->printAndLog(sprintf(self::TEXT_INFORMATION_URL_PATH_EXISTS, $url));

        $this->printAndLog(sprintf(
            self::TEXT_MESSAGE_DOWNLOAD,
            $url,
            $pathZip
        ));

        file_put_contents($pathZip, file_get_contents($url));

        $zip = new ZipArchive();
        if ($zip->open($pathZip) !== true) {
            $this->printAndLog(sprintf(self::TEXT_ERROR_UNZIP, $pathZip));
            return false;
        }

        $zip->extractTo(dirname($pathZip));
        $zip->close();

        unlink($pathZip);

        if (!file_exists($pathZipTxt)) {
            $this->printAndLog(sprintf(self::TEXT_ERROR_UNZIPPED_FILE_NOT_FOUND, $pathZipTxt));
            return false;
        }

        rename($pathZipTxt, $pathTxt);

        $this->printAndLog(sprintf('Data file saved to "%s".', $pathTxt));

        $this->printAndLog(sprintf(
            self::TEXT_MESSAGE_DOWNLOAD_README,
            self::URL_DOWNLOAD_README,
            $pathReadme
        ));

        file_put_contents($pathReadme, file_get_contents(self::URL_DOWNLOAD_README));

        if (!file_exists($pathReadme)) {
            $this->printAndLog(sprintf(self::TEXT_ERROR_FILE_NOT_FOUND, $pathReadme));
            return false;
        }

        $this->printAndLog(sprintf('Readme file saved to "%s".', $pathReadme));

        return true;
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

        if (!$this->createLogInstance(self::PATH_IMPORT_ZIP_CODE, 'download')) {
            $this->print('Unable to create log file.');
            return Command::FAILURE;
        }

        $urlZipCode = self::URL_DOWNLOAD_ZIP_CODE;

        $this->printAndLog(sprintf(self::TEXT_MESSAGE_DOWNLOAD_TRY, $urlZipCode));

        if (!$this->downloadLocationFile($urlZipCode)) {
            $this->printAndLog(sprintf(self::TEXT_ERROR_DOWNLOAD_LOCATION, $urlZipCode));
            return Command::FAILURE;
        }

        $this->printAndLog(sprintf(self::TEXT_SUCCESS_DOWNLOAD, $urlZipCode));

        /* Command successfully executed. */
        return Command::SUCCESS;
    }
}
