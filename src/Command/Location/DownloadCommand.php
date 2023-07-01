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

use App\Command\Base\Base;
use Exception;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\File\FileNotWriteableException;
use Ixnode\PhpTimezone\Constants\CountryUnknown;
use Ixnode\PhpTimezone\Country;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
 * @version 0.1.0 (2023-07-27)
 * @since 0.1.0 (2023-06-27) First version.
 * @example bin/console location:download [country]
 * @example bin/console location:download AT
 * @example bin/console location:download CH
 * @example bin/console location:download DE
 * @example bin/console location:download IT
 */
class DownloadCommand extends Base
{
    protected static $defaultName = 'location:download';

    protected const URL_DOWNLOAD = 'http://download.geonames.org/export/dump/%s.zip';

    protected const PATH_IMPORT = 'import/location';

    protected const TEXT_ERROR_COUNTRY_CODE_INVALID = 'The given country code "%s" is invalid.';

    protected const TEXT_ERROR_COUNTRY_CODE_UNKNOWN = 'The given country code "%s" is unknown.';

    protected const TEXT_ERROR_UNZIPPED_FILE_NOT_FOUND = 'Unable to find file "%s" from zip archive.';

    protected const TEXT_ERROR_UNZIP = 'Unable to unzip file "%s".';

    protected const TEXT_ERROR_DOWNLOAD = 'Unable to download file from %s.';

    protected const TEXT_ERROR_URL_PATH_DOES_NOT_EXIST = 'The given url "%s" does not exist.';

    protected const TEXT_INFORMATION_URL_PATH_EXISTS = 'The given url "%s" exists.';

    protected const TEXT_SUCCESS_DOWNLOAD = 'Successfully downloaded file from %s.';

    protected const TEXT_MESSAGE_DOWNLOAD_TRY = 'Try to download country %s (%s) from %s.';

    protected const TEXT_MESSAGE_DOWNLOAD = 'Download country %s (%s) from %s to %s.';

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setName(strval(self::$defaultName))
            ->setDescription('Downloads location from given url.')
            ->setDefinition([
                new InputArgument('country', InputArgument::REQUIRED, 'The country to be downloaded.'),
            ])
            ->setHelp(
                <<<'EOT'

The <info>location:download</info> command downloads locations from a given country code.

EOT
            );
    }

    /**
     * Download file from given url.
     *
     * @param string $countryCode
     * @param string $countryName
     * @param string $url
     * @return bool
     * @throws ClassInvalidException
     * @throws FileNotWriteableException
     */
    private function downloadFile(string $countryCode, string $countryName, string $url): bool
    {
        $pathTxt = sprintf('%s/%s.txt', self::PATH_IMPORT, $countryCode);
        $pathReadme = sprintf('%s/%s.readme.txt', self::PATH_IMPORT, $countryCode);

        $pathDownload = sprintf('%s/download/%s', self::PATH_IMPORT, $countryCode);
        $pathZip = sprintf('%s/%s.zip', $pathDownload, $countryCode);
        $pathZipTxt = sprintf('%s/%s.txt', $pathDownload, $countryCode);
        $pathZipReadme = sprintf('%s/readme.txt', $pathDownload);

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
            $countryName,
            $countryCode,
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

        if (!file_exists($pathZipReadme)) {
            $this->printAndLog(sprintf(self::TEXT_ERROR_UNZIPPED_FILE_NOT_FOUND, $pathZipReadme));
            return false;
        }

        rename($pathZipTxt, $pathTxt);
        rename($pathZipReadme, $pathReadme);

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

        if (!$this->createLogInstance(self::PATH_IMPORT, 'download')) {
            $this->print('Unable to create log file.');
            return Command::FAILURE;
        }

        $countryCodeGiven = (new TypeCastingHelper($input->getArgument('country')))->strval();

        $countryName = (new Country($countryCodeGiven))->getName();
        $countryCode = (new Country($countryCodeGiven))->getCode();

        if ($countryCode === CountryUnknown::COUNTRY_CODE_IV) {
            $this->printAndLog(sprintf(self::TEXT_ERROR_COUNTRY_CODE_INVALID, $countryCodeGiven));
            return Command::INVALID;
        }

        if ($countryCode === CountryUnknown::COUNTRY_CODE_UK) {
            $this->printAndLog(sprintf(self::TEXT_ERROR_COUNTRY_CODE_UNKNOWN, $countryCodeGiven));
            return Command::INVALID;
        }

        $url = sprintf(self::URL_DOWNLOAD, $countryCodeGiven);

        $this->printAndLog(sprintf(self::TEXT_MESSAGE_DOWNLOAD_TRY, $countryName, $countryCode, $url));

        if (!$this->downloadFile($countryCode, $countryName, $url)) {
            $this->printAndLog(sprintf(self::TEXT_ERROR_DOWNLOAD, $countryCode));
            return Command::FAILURE;
        }

        $this->printAndLog(sprintf(self::TEXT_SUCCESS_DOWNLOAD, $countryCode));

        /* Command successfully executed. */
        return Command::SUCCESS;
    }
}
