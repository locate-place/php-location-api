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
 * Class Version20240608174537
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-08)
 * @since 0.1.0 (2024-06-08) First version.
 */
final class Version20240608174537 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds api_key_id, created_at, is_valid index to table api_request_log.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_2862A3078BE312B38B8E8428C0FBD68F ON api_request_log (api_key_id, created_at, is_valid)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_2862A3078BE312B38B8E8428C0FBD68F');
    }
}
