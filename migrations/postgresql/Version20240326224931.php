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
 * Class Version20240326224931
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-25)
 * @since 0.1.0 (2024-03-25) First version.
 */
final class Version20240326224931 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Switch location.river to OneToMany relation.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location DROP CONSTRAINT fk_5e9e89cb41e62266');
        $this->addSql('DROP INDEX idx_5e9e89cb41e622663e3b8b90');
        $this->addSql('DROP INDEX idx_5e9e89cb41e62266');
        $this->addSql('DROP INDEX uniq_5e9e89cb41e62266');
        $this->addSql('ALTER TABLE location DROP river_id');
        $this->addSql('ALTER TABLE river ADD location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE river ADD CONSTRAINT FK_F5E3672B64D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_F5E3672B64D218E ON river (location_id)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD river_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT fk_5e9e89cb41e62266 FOREIGN KEY (river_id) REFERENCES river (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_5e9e89cb41e622663e3b8b90 ON location (river_id, ignore_mapping)');
        $this->addSql('CREATE INDEX idx_5e9e89cb41e62266 ON location (river_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_5e9e89cb41e62266 ON location (river_id)');
        $this->addSql('ALTER TABLE river DROP CONSTRAINT FK_F5E3672B64D218E');
        $this->addSql('DROP INDEX IDX_F5E3672B64D218E');
        $this->addSql('ALTER TABLE river DROP location_id');
    }
}
