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

namespace App\Command\AlternateName;

use App\Constants\Language\LanguageCode;
use App\Repository\AlternateNameRepository;
use App\Repository\LocationRepository;
use App\Utils\Wikipedia\Wikipedia;
use Doctrine\ORM\EntityManagerInterface;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class WikipediaCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-20)
 * @since 0.1.0 (2024-02-20) First version.
 *
 * @example bin/console alternate-name:wikipedia
 */
class WikipediaCommand extends Command
{
    protected static string $defaultName = 'alternate-name:wikipedia';

    private const NUMBER_FLUSH_AFTER = 10;

    private const ZERO_RESULT = 0;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LocationRepository $locationRepository
     * @param AlternateNameRepository $alternateNameRepository
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly LocationRepository $locationRepository,
        protected readonly AlternateNameRepository $alternateNameRepository
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
            ->setDescription('Add more wikipedia links for en, de and es.')
            ->setHelp(
                <<<'EOT'

The <info>alternate-name:wikipedia</info> command adds more wikipedia links for en, de and es.

EOT
            );
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
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws ClassInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TransportExceptionInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isoLanguage = LanguageCode::LINK;

        $locations = $this->locationRepository->findLocationsWithLinkIsoLanguage(
            isoLanguage: $isoLanguage,
            typeMustBeNull: true,
            limit: 100000
        );

        if (empty($locations)) {
            $output->writeln('No locations found with iso_language: ' . $isoLanguage);
            return Command::FAILURE;
        }

        $output->writeln(count($locations).' locations found with iso_language: '.$isoLanguage);

        $counter = 0;

        foreach ($locations as $index => $location) {
            $output->writeln('Index: '.$index);
            $output->writeln('-------');
            $alternateNames = $this->alternateNameRepository->findByIsoLanguage($location, $isoLanguage);

            if (count($alternateNames) <= self::ZERO_RESULT) {
                continue;
            }

            $wikipedia = new Wikipedia($alternateNames, true);

            /* Checks and adds en, de and es languages. */
            $wikipedia->addMissingLanguages();

            $alternateNamesWikipedia = $wikipedia->getAlternateNamesWikipedia();
            $alternateNamesOther = $wikipedia->getAlternateNamesOther();

            if (count($alternateNamesWikipedia) > self::ZERO_RESULT) {
                foreach ($alternateNamesWikipedia as $language => $alternateNameWikipedia) {
                    $output->writeln('New:      '.(is_null($alternateNameWikipedia->getId()) ? 'Yes' : sprintf('No: id = %d', $alternateNameWikipedia->getId())));
                    $output->writeln('Link:     '.$alternateNameWikipedia->getAlternateName());
                    $output->writeln('Type:     '.$alternateNameWikipedia->getType());
                    $output->writeln('Source:   '.$alternateNameWikipedia->getSource());
                    $output->writeln('AN ID:    '.$alternateNameWikipedia->getAlternateNameId());
                    $output->writeln('Language: '.$language);
                    $output->writeln('');

                    $this->entityManager->persist($alternateNameWikipedia);
                }
            }

            if (count($alternateNamesOther) > self::ZERO_RESULT) {
                foreach ($alternateNamesOther as $alternateNameOther) {
                    $output->writeln('New:      '.(is_null($alternateNameOther->getId()) ? 'Yes' : sprintf('No: id = %d', $alternateNameOther->getId())));
                    $output->writeln('Link:     '.$alternateNameOther->getAlternateName());
                    $output->writeln('Type:     '.$alternateNameOther->getType());
                    $output->writeln('Source:   '.$alternateNameOther->getSource());
                    $output->writeln('AN ID:    '.$alternateNameOther->getAlternateNameId());
                    $output->writeln('');

                    $this->entityManager->persist($alternateNameOther);
                }
            }

            $counter++;

            if ($counter >= self::NUMBER_FLUSH_AFTER) {
                $this->entityManager->flush();
                $counter = 0;

                $output->writeln('----------------');
                $output->writeln('Records flushed.');
                $output->writeln('----------------');
                $output->writeln('');
            }
        }

        return Command::SUCCESS;
    }
}
