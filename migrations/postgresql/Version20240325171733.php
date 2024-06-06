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

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20240325171733
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-25)
 * @since 0.1.0 (2024-03-25) First version.
 */
final class Version20240325171733 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Move OneToOne relation from river table to location table.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD river_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD ignore_mapping BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB41E62266 FOREIGN KEY (river_id) REFERENCES river (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5E9E89CB41E62266 ON location (river_id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CB41E62266 ON location (river_id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CB3E3B8B90 ON location (ignore_mapping)');
        $this->addSql('ALTER TABLE river DROP CONSTRAINT fk_f5e3672b64d218e');
        $this->addSql('DROP INDEX idx_f5e3672b64d218e');
        $this->addSql('DROP INDEX idx_f5e3672b3e3b8b90');
        $this->addSql('DROP INDEX uniq_f5e3672b64d218e');
        $this->addSql('ALTER TABLE river DROP location_id');
        $this->addSql('ALTER TABLE river DROP ignore_mapping');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE river ADD location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE river ADD ignore_mapping BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE river ADD CONSTRAINT fk_f5e3672b64d218e FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_f5e3672b64d218e ON river (location_id)');
        $this->addSql('CREATE INDEX idx_f5e3672b3e3b8b90 ON river (ignore_mapping)');
        $this->addSql('CREATE UNIQUE INDEX uniq_f5e3672b64d218e ON river (location_id)');
        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CB41E62266');
        $this->addSql('DROP INDEX UNIQ_5E9E89CB41E62266');
        $this->addSql('DROP INDEX IDX_5E9E89CB41E62266');
        $this->addSql('DROP INDEX IDX_5E9E89CB3E3B8B90');
        $this->addSql('ALTER TABLE location DROP river_id');
        $this->addSql('ALTER TABLE location DROP ignore_mapping');
    }
}
