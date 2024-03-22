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
 * Class Version20240321215904
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-21)
 * @since 0.1.0 (2024-03-21) First version.
 */
final class Version20240321215904 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds river table and foreign keys.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE river_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE river (id INT NOT NULL, location_id INT DEFAULT NULL, river_code BIGINT NOT NULL, name VARCHAR(1024) NOT NULL, length NUMERIC(10, 2) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5E3672B64D218E ON river (location_id)');
        $this->addSql('CREATE INDEX IDX_F5E3672B5E237E06 ON river (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5E3672BF8BFEA2D ON river (river_code)');
        $this->addSql('COMMENT ON COLUMN river.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN river.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE river ADD CONSTRAINT FK_F5E3672B64D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE river_part ADD river_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE river_part ADD CONSTRAINT FK_C6A5AA7341E62266 FOREIGN KEY (river_id) REFERENCES river (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C6A5AA7341E62266 ON river_part (river_id)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE river_part DROP CONSTRAINT FK_C6A5AA7341E62266');
        $this->addSql('DROP SEQUENCE river_id_seq CASCADE');
        $this->addSql('ALTER TABLE river DROP CONSTRAINT FK_F5E3672B64D218E');
        $this->addSql('DROP TABLE river');
        $this->addSql('DROP INDEX IDX_C6A5AA7341E62266');
        $this->addSql('ALTER TABLE river_part DROP river_id');
    }
}
