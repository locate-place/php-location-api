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
 * Class Version20230627192201
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-29)
 * @since 0.1.0 (2023-06-29) First version.
 */
final class Version20230627192201 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds the first implementation of all tables and columns.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE admin_code_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE country_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE feature_class_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE feature_code_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE location_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE timezone_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE version_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE admin_code (id INT NOT NULL, country_id INT NOT NULL, admin1_code VARCHAR(20) DEFAULT NULL, admin2_code VARCHAR(80) DEFAULT NULL, admin3_code VARCHAR(20) DEFAULT NULL, admin4_code VARCHAR(20) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_570CB278F92F3E70 ON admin_code (country_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_570CB278B8E4D5533E70A7FDF52C7458E82944E0F92F3E70 ON admin_code (admin1_code, admin2_code, admin3_code, admin4_code, country_id)');
        $this->addSql('COMMENT ON COLUMN admin_code.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN admin_code.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE country (id INT NOT NULL, code VARCHAR(2) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5373C96677153098 ON country (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5373C96677153098 ON country (code)');
        $this->addSql('COMMENT ON COLUMN country.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN country.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE feature_class (id INT NOT NULL, class VARCHAR(1) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_24151396ED4B199F ON feature_class (class)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_24151396ED4B199F ON feature_class (class)');
        $this->addSql('COMMENT ON COLUMN feature_class.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN feature_class.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE feature_code (id INT NOT NULL, class_id INT NOT NULL, code VARCHAR(10) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_253FBFEBEA000B10 ON feature_code (class_id)');
        $this->addSql('CREATE INDEX IDX_253FBFEB77153098 ON feature_code (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_253FBFEB77153098 ON feature_code (code)');
        $this->addSql('COMMENT ON COLUMN feature_code.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN feature_code.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE location (id INT NOT NULL, feature_class_id INT NOT NULL, feature_code_id INT NOT NULL, country_id INT NOT NULL, timezone_id INT NOT NULL, admin_code_id INT NOT NULL, geoname_id INT NOT NULL, name VARCHAR(1024) NOT NULL, ascii_name VARCHAR(1024) NOT NULL, alternate_names VARCHAR(4096) NOT NULL, coordinate POINT NOT NULL, cc2 VARCHAR(200) NOT NULL, population BIGINT DEFAULT NULL, elevation INT DEFAULT NULL, dem INT DEFAULT NULL, modification_date DATE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5E9E89CB93EDA42F ON location (feature_class_id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CB16CD651E ON location (feature_code_id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CBF92F3E70 ON location (country_id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CB3FE997DE ON location (timezone_id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CBE3E67C9E ON location (admin_code_id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CBCB9CBA17 ON location USING gist (coordinate)');
        $this->addSql('COMMENT ON COLUMN location.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN location.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE timezone (id INT NOT NULL, timezone VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3701B2973701B297 ON timezone (timezone)');
        $this->addSql('COMMENT ON COLUMN timezone.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN timezone.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE timezone_country (timezone_id INT NOT NULL, country_id INT NOT NULL, PRIMARY KEY(timezone_id, country_id))');
        $this->addSql('CREATE INDEX IDX_6DC97C493FE997DE ON timezone_country (timezone_id)');
        $this->addSql('CREATE INDEX IDX_6DC97C49F92F3E70 ON timezone_country (country_id)');
        $this->addSql('CREATE TABLE version (id INT NOT NULL, version VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN version.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN version.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE admin_code ADD CONSTRAINT FK_570CB278F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE feature_code ADD CONSTRAINT FK_253FBFEBEA000B10 FOREIGN KEY (class_id) REFERENCES feature_class (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB93EDA42F FOREIGN KEY (feature_class_id) REFERENCES feature_class (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB16CD651E FOREIGN KEY (feature_code_id) REFERENCES feature_code (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CBF92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB3FE997DE FOREIGN KEY (timezone_id) REFERENCES timezone (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CBE3E67C9E FOREIGN KEY (admin_code_id) REFERENCES admin_code (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE timezone_country ADD CONSTRAINT FK_6DC97C493FE997DE FOREIGN KEY (timezone_id) REFERENCES timezone (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE timezone_country ADD CONSTRAINT FK_6DC97C49F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE admin_code_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE country_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE feature_class_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE feature_code_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE location_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE timezone_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE version_id_seq CASCADE');
        $this->addSql('ALTER TABLE admin_code DROP CONSTRAINT FK_570CB278F92F3E70');
        $this->addSql('ALTER TABLE feature_code DROP CONSTRAINT FK_253FBFEBEA000B10');
        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CB93EDA42F');
        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CB16CD651E');
        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CBF92F3E70');
        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CB3FE997DE');
        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CBE3E67C9E');
        $this->addSql('ALTER TABLE timezone_country DROP CONSTRAINT FK_6DC97C493FE997DE');
        $this->addSql('ALTER TABLE timezone_country DROP CONSTRAINT FK_6DC97C49F92F3E70');
        $this->addSql('DROP TABLE admin_code');
        $this->addSql('DROP TABLE country');
        $this->addSql('DROP TABLE feature_class');
        $this->addSql('DROP TABLE feature_code');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE timezone');
        $this->addSql('DROP TABLE timezone_country');
        $this->addSql('DROP TABLE version');
    }
}
