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
 * Class Version20240316144146
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-16)
 * @since 0.1.0 (2024-03-16) First version.
 */
final class Version20240316144146 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds zip_code_area table.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE zip_code_area_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE zip_code_area (id INT NOT NULL, country_id INT NOT NULL, type VARCHAR(10) NOT NULL, zip_code VARCHAR(20) NOT NULL, place_name VARCHAR(180) NOT NULL, population INT DEFAULT NULL, area NUMERIC(10, 4) DEFAULT NULL, population_density NUMERIC(10, 1) DEFAULT NULL, coordinates geography(POLYGON, 4326) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8B6FC688F92F3E70 ON zip_code_area (country_id)');
        $this->addSql('CREATE INDEX IDX_8B6FC6889816D676 ON zip_code_area USING gist (coordinates)');
        $this->addSql('CREATE INDEX IDX_8B6FC688A1ACE158 ON zip_code_area (zip_code)');
        $this->addSql('CREATE INDEX IDX_8B6FC68894960EEA ON zip_code_area (place_name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8B6FC688F92F3E7094960EEAA1ACE158 ON zip_code_area (country_id, place_name, zip_code)');
        $this->addSql('COMMENT ON COLUMN zip_code_area.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN zip_code_area.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE zip_code_area ADD CONSTRAINT FK_8B6FC688F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE zip_code_area_id_seq CASCADE');
        $this->addSql('ALTER TABLE zip_code_area DROP CONSTRAINT FK_8B6FC688F92F3E70');
        $this->addSql('DROP TABLE zip_code_area');
    }
}
