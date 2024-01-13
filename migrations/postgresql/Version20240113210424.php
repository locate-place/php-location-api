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

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20240113210424
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-13`)
 * @since 0.1.0 (2024-01-13) First version.
 */
final class Version20240113210424 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds alternate_name index to table alternate_name and name index to location.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_AD89C8ABAD89C8AB ON alternate_name (alternate_name)');
        $this->addSql('CREATE INDEX IDX_5E9E89CB5E237E06 ON location (name)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_AD89C8ABAD89C8AB');
        $this->addSql('DROP INDEX IDX_5E9E89CB5E237E06');
    }
}
