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
 * Class Version20240319191919
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-19)
 * @since 0.1.0 (2024-03-19) First version.
 */
final class Version20240319191919 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Rename table river to river_part. Rename some fields to more readable names.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE river RENAME TO river_part');
        $this->addSql('ALTER TABLE river_part RENAME COLUMN eu_seg_cd TO european_segment_code');
        $this->addSql('ALTER TABLE river_part RENAME COLUMN land_cd TO country_state_code');
        $this->addSql('ALTER TABLE river_part RENAME COLUMN rbd_cd TO river_basin_district_code');
        $this->addSql('ALTER TABLE river_part RENAME COLUMN river_cd TO river_code');
        $this->addSql('ALTER TABLE river_part RENAME COLUMN wa_cd TO work_area_code');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE river_part RENAME COLUMN european_segment_code TO eu_seg_cd');
        $this->addSql('ALTER TABLE river_part RENAME COLUMN country_state_code TO land_cd');
        $this->addSql('ALTER TABLE river_part RENAME COLUMN river_basin_district_code TO rbd_cd');
        $this->addSql('ALTER TABLE river_part RENAME COLUMN river_code TO river_cd');
        $this->addSql('ALTER TABLE river_part RENAME COLUMN work_area_code TO wa_cd');
        $this->addSql('ALTER TABLE river_part RENAME TO river');
    }
}
