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
 * Class Version20230827100501
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-27)
 * @since 0.1.0 (2023-08-27) First version.
 */
final class Version20230827100501 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Creates alternate_name table.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE alternate_name_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE alternate_name (id INT NOT NULL, location_id INT NOT NULL, alternate_name VARCHAR(400) NOT NULL, iso_language VARCHAR(7) DEFAULT NULL, is_preferred_name BOOLEAN NOT NULL, is_short_name BOOLEAN NOT NULL, is_colloquial BOOLEAN NOT NULL, is_historic BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AD89C8AB64D218E ON alternate_name (location_id)');
        $this->addSql('ALTER TABLE alternate_name ADD CONSTRAINT FK_AD89C8AB64D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE location DROP alternate_names');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE alternate_name_id_seq CASCADE');
        $this->addSql('ALTER TABLE alternate_name DROP CONSTRAINT FK_AD89C8AB64D218E');
        $this->addSql('DROP TABLE alternate_name');
        $this->addSql('ALTER TABLE location ADD alternate_names VARCHAR(16384) NOT NULL');
    }
}
