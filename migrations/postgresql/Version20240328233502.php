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
 * Class Version20240328233502
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-25)
 * @since 0.1.0 (2024-03-25) First version.
 */
final class Version20240328233502 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds field mapping_river_similarity and mapping_river_ignore to location table.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_5e9e89cb3e3b8b90');
        $this->addSql('ALTER TABLE location ADD mapping_river_similarity NUMERIC(3, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE location RENAME COLUMN ignore_mapping TO mapping_river_ignore');
        $this->addSql('CREATE INDEX IDX_5E9E89CB8246CA10 ON location (mapping_river_ignore)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_5E9E89CB8246CA10');
        $this->addSql('ALTER TABLE location DROP mapping_river_similarity');
        $this->addSql('ALTER TABLE location RENAME COLUMN mapping_river_ignore TO ignore_mapping');
        $this->addSql('CREATE INDEX idx_5e9e89cb3e3b8b90 ON location (ignore_mapping)');
    }
}
