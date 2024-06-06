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
 * Class Version20230731151006
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 */
final class Version20230731151006 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Converts the field coordinate of table location to geography(GEOGRAPHY, 4326). Copies the data from coordinate_geography to coordinate.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ALTER coordinate TYPE Geography(POINT, 4326) USING CASE WHEN coordinate IS NOT NULL THEN ST_SetSRID(ST_MakePoint(coordinate[1], coordinate[0]), 4326) END');
        /* Copies the data from coordinate_geography to coordinate. */
        $this->addSql('UPDATE location SET coordinate = coordinate_geography');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ALTER coordinate TYPE POINT');
    }
}
