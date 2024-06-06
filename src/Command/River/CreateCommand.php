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

namespace App\Command\River;

use App\Entity\River;
use App\Repository\RiverPartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateCommand.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-23)
 * @since 0.1.0 (2024-03-23) First version.
 * @example bin/console river:create
 */
class CreateCommand extends Command
{
    protected static string $defaultName = 'river:create';

    /**
     * @param EntityManagerInterface $entityManager
     * @param RiverPartRepository $riverPartRepository
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly RiverPartRepository $riverPartRepository
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
            ->setDescription('Creates river entries from river_part entries.')
            ->setHelp(
                <<<'EOT'

The <info>river:create</info> creates river entries from river_part entries.

EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $riverCodes = $this->riverPartRepository->getUniqueRiverCodes();

        $counter = 0;
        foreach ($riverCodes as $riverCode => $riverName) {
            if (is_null($riverName)) {
                continue;
            }

            $riverParts = $this->riverPartRepository->findBy(['riverCode' => $riverCode]);

            $riverNames = [];

            foreach ($riverParts as $riverPart) {
                $riverName = $riverPart->getName();

                if (is_null($riverName)) {
                    continue;
                }

                $splitName = explode('/', $riverName);

                foreach ($splitName as $name) {
                    if ($name === 'unbekannt') {
                        continue;
                    }

                    $riverNames[$name] = true;
                }
            }

            $riverNamesString = implode('/', array_keys($riverNames));

            $river = new River();
            $river->setRiverCode((string) $riverCode);
            $river->setName($riverNamesString);

            $length = 0;
            foreach ($riverParts as $riverPart) {
                $riverPart->setRiver($river);
                $this->entityManager->persist($riverPart);

                $length += $riverPart->getLength();
            }

            $river->setLength((string) $length);
            $this->entityManager->persist($river);

            if ($counter % 100 === 0) {
                $output->writeln(sprintf('Created %d river entries.', $counter));
                $this->entityManager->flush();
            }

            $counter++;
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
