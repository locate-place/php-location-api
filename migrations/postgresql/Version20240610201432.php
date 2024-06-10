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
 * Class Version20240610201432
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-10)
 * @since 0.1.0 (2024-06-10) First version.
 */
final class Version20240610201432 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds the field time_taken and memory_taken to table api_request_log.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_request_log ADD time_taken INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE api_request_log ADD memory_taken DOUBLE PRECISION DEFAULT 0 NOT NULL');
        $this->addSql('COMMENT ON COLUMN api_request_log.time_taken IS \'Time in ms\'');
        $this->addSql('COMMENT ON COLUMN api_request_log.memory_taken IS \'Memory in MB\'');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_request_log DROP time_taken');
        $this->addSql('ALTER TABLE api_request_log DROP memory_taken');
    }
}
