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
 * Class Version20230827103440
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-27)
 * @since 0.1.0 (2023-08-27) First version.
 */
final class Version20230827103440 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Removes the is_ prefix from some columns in the alternate_name table.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE alternate_name ADD preferred_name BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE alternate_name ADD short_name BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE alternate_name ADD colloquial BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE alternate_name ADD historic BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE alternate_name DROP is_preferred_name');
        $this->addSql('ALTER TABLE alternate_name DROP is_short_name');
        $this->addSql('ALTER TABLE alternate_name DROP is_colloquial');
        $this->addSql('ALTER TABLE alternate_name DROP is_historic');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE alternate_name ADD is_preferred_name BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE alternate_name ADD is_short_name BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE alternate_name ADD is_colloquial BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE alternate_name ADD is_historic BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE alternate_name DROP preferred_name');
        $this->addSql('ALTER TABLE alternate_name DROP short_name');
        $this->addSql('ALTER TABLE alternate_name DROP colloquial');
        $this->addSql('ALTER TABLE alternate_name DROP historic');
    }
}
