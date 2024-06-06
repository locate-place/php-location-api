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
 * Class Version20240316155620
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-16)
 * @since 0.1.0 (2024-03-16) First version.
 */
final class Version20240316155834 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Add field number to table zip_code_area.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_8b6fc688f92f3e7094960eeaa1ace158');
        $this->addSql('ALTER TABLE zip_code_area ADD number INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8B6FC688F92F3E7094960EEAA1ACE15896901F54 ON zip_code_area (country_id, place_name, zip_code, number)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8B6FC688F92F3E7094960EEAA1ACE15896901F54');
        $this->addSql('ALTER TABLE zip_code_area DROP number');
        $this->addSql('CREATE UNIQUE INDEX uniq_8b6fc688f92f3e7094960eeaa1ace158 ON zip_code_area (country_id, place_name, zip_code)');
    }
}
