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
 * Class Version20230731153825
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 */
final class Version20230731153825 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Set location.coordinate to NOT NULL. Drop unused coordinate_geography field and index.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_5e9e89cb1d1a2d1c');
        $this->addSql('ALTER TABLE location DROP coordinate_geography');
        $this->addSql('ALTER TABLE location ALTER coordinate SET NOT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD coordinate_geography geography(GEOGRAPHY, 4326) NOT NULL');
        $this->addSql('ALTER TABLE location ALTER coordinate DROP NOT NULL');
        $this->addSql('CREATE INDEX idx_5e9e89cb1d1a2d1c ON location (coordinate_geography)');
    }
}
