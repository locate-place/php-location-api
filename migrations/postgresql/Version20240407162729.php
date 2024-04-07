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
 * Class Version20240407162729
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.1 (2024-04-07)
 * @since 0.1.0 (2024-04-07) First version.
 */
final class Version20240407162729 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds search_index table.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE search_index_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE search_index (id INT NOT NULL, location_id INT NOT NULL, search_text_simple tsvector DEFAULT NULL, search_text_de tsvector DEFAULT NULL, search_text_en tsvector DEFAULT NULL, search_text_es tsvector DEFAULT NULL, relevance_score INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B446A4E864D218E ON search_index (location_id)');
        $this->addSql('CREATE INDEX IDX_B446A4E8AF9E396F ON search_index (search_text_simple)');
        $this->addSql('CREATE INDEX IDX_B446A4E826F59409 ON search_index (search_text_de)');
        $this->addSql('CREATE INDEX IDX_B446A4E8A83C7CC0 ON search_index (search_text_en)');
        $this->addSql('CREATE INDEX IDX_B446A4E8CB3A1019 ON search_index (search_text_es)');
        $this->addSql('ALTER TABLE search_index ADD CONSTRAINT FK_B446A4E864D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE search_index_id_seq CASCADE');
        $this->addSql('ALTER TABLE search_index DROP CONSTRAINT FK_B446A4E864D218E');
        $this->addSql('DROP TABLE search_index');
    }
}
