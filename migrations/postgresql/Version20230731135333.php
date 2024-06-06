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
 * Class Version20230731135333
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 */
final class Version20230731135333 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds field coordinate_geography to table location. Writes all coordinates from location.coordinate to location.coordinate_geography.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD coordinate_geography geography(POINT, 4326) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_5E9E89CB1D1A2D1C ON location USING gist (coordinate_geography)');
        $this->addSql('CREATE INDEX IDX_5E9E89CB23F5422B ON location (geoname_id)');

        /* Writes all coordinates from location.coordinate to location.coordinate_geography. */
        $this->addSql('UPDATE location SET coordinate_geography = ST_SetSRID(ST_MakePoint(coordinate[1], coordinate[0]), 4326)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_5E9E89CB1D1A2D1C');
        $this->addSql('DROP INDEX IDX_5E9E89CB23F5422B');
        $this->addSql('ALTER TABLE location DROP coordinate_geography');
    }
}
