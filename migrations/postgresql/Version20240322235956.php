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
 * Class Version20240322235956
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-23)
 * @since 0.1.0 (2024-03-23) First version.
 */
final class Version20240322235956 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds comments to coordinate values.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN location.coordinate IS \'Point,4326\'');
        $this->addSql('COMMENT ON COLUMN river_part.coordinates IS \'LineString,4326\'');
        $this->addSql('COMMENT ON COLUMN zip_code.coordinate IS \'Point,4326\'');
        $this->addSql('COMMENT ON COLUMN zip_code_area.coordinates IS \'Polygon,4326\'');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN location.coordinate IS NULL');
        $this->addSql('COMMENT ON COLUMN river_part.coordinates IS NULL');
        $this->addSql('COMMENT ON COLUMN zip_code.coordinate IS NULL');
        $this->addSql('COMMENT ON COLUMN zip_code_area.coordinates IS NULL');
    }
}
