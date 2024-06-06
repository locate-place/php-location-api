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
 * Class Version20240319211515
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-19)
 * @since 0.1.0 (2024-03-19) First version.
 */
final class Version20240319211515 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Renames the SEQUENCE river_id_seq RENAME TO river_part_id_seq.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER SEQUENCE river_id_seq RENAME TO river_part_id_seq');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER SEQUENCE river_part_id_seq RENAME TO river_id_seq');
    }
}
