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
 * Class Version20240329191042
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-29)
 * @since 0.1.0 (2024-03-29) First version.
 */
final class Version20240329191042 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds many to many table location_river.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE location_river (location_id INT NOT NULL, river_id INT NOT NULL, PRIMARY KEY(location_id, river_id))');
        $this->addSql('CREATE INDEX IDX_83BF220E64D218E ON location_river (location_id)');
        $this->addSql('CREATE INDEX IDX_83BF220E41E62266 ON location_river (river_id)');
        $this->addSql('ALTER TABLE location_river ADD CONSTRAINT FK_83BF220E64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE location_river ADD CONSTRAINT FK_83BF220E41E62266 FOREIGN KEY (river_id) REFERENCES river (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE river DROP CONSTRAINT fk_f5e3672b64d218e');
        $this->addSql('DROP INDEX idx_f5e3672b64d218e');
        $this->addSql('ALTER TABLE river DROP location_id');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location_river DROP CONSTRAINT FK_83BF220E64D218E');
        $this->addSql('ALTER TABLE location_river DROP CONSTRAINT FK_83BF220E41E62266');
        $this->addSql('DROP TABLE location_river');
        $this->addSql('ALTER TABLE river ADD location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE river ADD CONSTRAINT fk_f5e3672b64d218e FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_f5e3672b64d218e ON river (location_id)');
    }
}
