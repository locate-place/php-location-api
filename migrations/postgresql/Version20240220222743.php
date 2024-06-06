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
 * Class Version20240220222743
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-20)
 * @since 0.1.0 (2024-02-20) First version.
 */
final class Version20240220222743 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds table property. Adds field type and source to alternate_name table. Adds field source to location table.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        /* table property */
        $this->addSql('CREATE SEQUENCE property_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE property (id INT NOT NULL, location_id INT NOT NULL, property_name VARCHAR(200) NOT NULL, property_value VARCHAR(1024) NOT NULL, property_language VARCHAR(7) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8BF21CDE64D218E ON property (location_id)');
        $this->addSql('CREATE INDEX IDX_8BF21CDE413BC13C ON property (property_name)');
        $this->addSql('CREATE INDEX IDX_8BF21CDEDB649939 ON property (property_value)');
        $this->addSql('CREATE INDEX IDX_8BF21CDE8A5C45F1 ON property (property_language)');
        $this->addSql('COMMENT ON COLUMN property.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN property.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE64D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        /* table alternate_name */
        $this->addSql('ALTER TABLE alternate_name ADD type VARCHAR(12) DEFAULT NULL');
        $this->addSql('ALTER TABLE alternate_name ADD source VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_AD89C8AB8CDE5729 ON alternate_name (type)');
        $this->addSql('CREATE INDEX IDX_AD89C8AB5F8A7F73 ON alternate_name (source)');

        /* table location */
        $this->addSql('ALTER TABLE location ADD source VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_5E9E89CB5F8A7F73 ON location (source)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE property_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE topology.topology_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE property DROP CONSTRAINT FK_8BF21CDE64D218E');
        $this->addSql('DROP TABLE property');
        $this->addSql('DROP INDEX IDX_AD89C8AB8CDE5729');
        $this->addSql('DROP INDEX IDX_AD89C8AB5F8A7F73');
        $this->addSql('ALTER TABLE alternate_name DROP type');
        $this->addSql('ALTER TABLE alternate_name DROP source');
        $this->addSql('DROP INDEX IDX_5E9E89CB5F8A7F73');
        $this->addSql('ALTER TABLE location DROP source');
    }
}
