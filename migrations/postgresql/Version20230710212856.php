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
 * Class Version20230710212856
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-10)
 * @since 0.1.0 (2023-07-10) First version.
 */
final class Version20230710212856 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds import table.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE import_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE import (id INT NOT NULL, country_id INT NOT NULL, path VARCHAR(1024) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9D4ECE1DF92F3E70 ON import (country_id)');
        $this->addSql('COMMENT ON COLUMN import.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN import.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE location_import (location_id INT NOT NULL, import_id INT NOT NULL, PRIMARY KEY(location_id, import_id))');
        $this->addSql('CREATE INDEX IDX_D63C461F64D218E ON location_import (location_id)');
        $this->addSql('CREATE INDEX IDX_D63C461FB6A263D9 ON location_import (import_id)');
        $this->addSql('ALTER TABLE import ADD CONSTRAINT FK_9D4ECE1DF92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE location_import ADD CONSTRAINT FK_D63C461F64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE location_import ADD CONSTRAINT FK_D63C461FB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE import_id_seq CASCADE');
        $this->addSql('ALTER TABLE import DROP CONSTRAINT FK_9D4ECE1DF92F3E70');
        $this->addSql('ALTER TABLE location_import DROP CONSTRAINT FK_D63C461F64D218E');
        $this->addSql('ALTER TABLE location_import DROP CONSTRAINT FK_D63C461FB6A263D9');
        $this->addSql('DROP TABLE import');
        $this->addSql('DROP TABLE location_import');
    }
}
