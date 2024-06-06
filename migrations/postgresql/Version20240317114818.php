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
 * Class Version20240317114818
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-17)
 * @since 0.1.0 (2024-03-17) First version.
 */
final class Version20240317114818 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds river table.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE river_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE river (id INT NOT NULL, country_id INT NOT NULL, name VARCHAR(1024) NOT NULL, length NUMERIC(10, 2) DEFAULT NULL, coordinates geography(LINESTRING, 4326) NOT NULL, object_id INT NOT NULL, continua VARCHAR(1) NOT NULL, eu_seg_cd VARCHAR(1024) NOT NULL, flow_direction VARCHAR(64) NOT NULL, land_cd VARCHAR(4) NOT NULL, rbd_cd INT NOT NULL, river_cd INT NOT NULL, scale VARCHAR(1) NOT NULL, template VARCHAR(64) NOT NULL, wa_cd INT NOT NULL, metadata VARCHAR(1024) DEFAULT NULL, number INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F5E3672BF92F3E70 ON river (country_id)');
        $this->addSql('CREATE INDEX IDX_F5E3672B9816D676 ON river USING gist (coordinates)');
        $this->addSql('CREATE INDEX IDX_F5E3672B5E237E06 ON river (name)');
        $this->addSql('CREATE INDEX IDX_F5E3672B17D9EB2 ON river (length)');
        $this->addSql('COMMENT ON COLUMN river.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN river.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE river ADD CONSTRAINT FK_F5E3672BF92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE river_id_seq CASCADE');
        $this->addSql('ALTER TABLE river DROP CONSTRAINT FK_F5E3672BF92F3E70');
        $this->addSql('DROP TABLE river');
    }
}
