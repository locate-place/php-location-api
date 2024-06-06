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
 * Class Version20240314212533
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-14)
 * @since 0.1.0 (2024-03-14) First version.
 */
final class Version20240314212533 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds name fields to admin_code table. Adds zip_code table.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE zip_code_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE zip_code (id INT NOT NULL, country_id INT NOT NULL, admin_code_id INT NOT NULL, place_name VARCHAR(180) NOT NULL, postal_code VARCHAR(20) NOT NULL, coordinate geography(POINT, 4326) NOT NULL, accuracy INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A1ACE158F92F3E70 ON zip_code (country_id)');
        $this->addSql('CREATE INDEX IDX_A1ACE158E3E67C9E ON zip_code (admin_code_id)');
        $this->addSql('CREATE INDEX IDX_A1ACE158CB9CBA17 ON zip_code USING gist (coordinate)');
        $this->addSql('CREATE INDEX IDX_A1ACE15894960EEA ON zip_code (place_name)');
        $this->addSql('CREATE INDEX IDX_A1ACE158EA98E376 ON zip_code (postal_code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A1ACE158F92F3E70E3E67C9E94960EEAEA98E376 ON zip_code (country_id, admin_code_id, place_name, postal_code)');
        $this->addSql('COMMENT ON COLUMN zip_code.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN zip_code.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE zip_code ADD CONSTRAINT FK_A1ACE158F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE zip_code ADD CONSTRAINT FK_A1ACE158E3E67C9E FOREIGN KEY (admin_code_id) REFERENCES admin_code (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE admin_code ADD admin1_code2 VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE admin_code ADD admin1_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE admin_code ADD admin2_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE admin_code ADD admin3_name VARCHAR(100) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE zip_code_id_seq CASCADE');
        $this->addSql('ALTER TABLE zip_code DROP CONSTRAINT FK_A1ACE158F92F3E70');
        $this->addSql('ALTER TABLE zip_code DROP CONSTRAINT FK_A1ACE158E3E67C9E');
        $this->addSql('DROP TABLE zip_code');
        $this->addSql('ALTER TABLE admin_code DROP admin1_code2');
        $this->addSql('ALTER TABLE admin_code DROP admin1_name');
        $this->addSql('ALTER TABLE admin_code DROP admin2_name');
        $this->addSql('ALTER TABLE admin_code DROP admin3_name');
    }
}
