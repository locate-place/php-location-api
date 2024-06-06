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
 * Class Version20240226210646
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-24)
 * @since 0.1.0 (2024-02-24) First version.
 */
final class Version20240226210646 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds table source. Adds source_id and property_type to table property.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE source_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE source (id INT NOT NULL, source_type VARCHAR(63) NOT NULL, source_link VARCHAR(2048) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5F8A7F738D54D22A ON source (source_type)');
        $this->addSql('CREATE INDEX IDX_5F8A7F7337261CF2 ON source (source_link)');
        $this->addSql('COMMENT ON COLUMN source.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN source.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE property ADD source_id INT NOT NULL');
        $this->addSql('ALTER TABLE property ADD property_type VARCHAR(63) DEFAULT NULL');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE953C1C61 FOREIGN KEY (source_id) REFERENCES source (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_8BF21CDE953C1C61 ON property (source_id)');
        $this->addSql('CREATE INDEX IDX_8BF21CDE93C6E813 ON property (property_type)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8BF21CDE413BC13C93C6E8138A5C45F1 ON property (property_name, property_type, property_language)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property DROP CONSTRAINT FK_8BF21CDE953C1C61');
        $this->addSql('DROP SEQUENCE source_id_seq CASCADE');
        $this->addSql('DROP TABLE source');
        $this->addSql('DROP INDEX IDX_8BF21CDE953C1C61');
        $this->addSql('DROP INDEX IDX_8BF21CDE93C6E813');
        $this->addSql('DROP INDEX UNIQ_8BF21CDE413BC13C93C6E8138A5C45F1');
        $this->addSql('ALTER TABLE property DROP source_id');
        $this->addSql('ALTER TABLE property DROP property_type');
    }
}
