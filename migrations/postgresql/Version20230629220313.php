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
 * Class Version20230627192201
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-29)
 * @since 0.1.0 (2023-06-29) First version.
 */
final class Version20230629220313 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Fixes the UniqueConstraint constraint on feature_code table.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_253fbfeb77153098');
        $this->addSql('DROP INDEX idx_253fbfeb77153098');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_253FBFEBEA000B1077153098 ON feature_code (class_id, code)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_253FBFEBEA000B1077153098');
        $this->addSql('CREATE UNIQUE INDEX uniq_253fbfeb77153098 ON feature_code (code)');
        $this->addSql('CREATE INDEX idx_253fbfeb77153098 ON feature_code (code)');
    }
}
