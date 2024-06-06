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
 * Class Version20240321221330
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-19)
 * @since 0.1.0 (2024-03-19) First version.
 */
final class Version20240321221330 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Rename indexes.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX idx_f5e3672bf92f3e70 RENAME TO IDX_C6A5AA73F92F3E70');
        $this->addSql('ALTER INDEX idx_f5e3672b9816d676 RENAME TO IDX_C6A5AA739816D676');
        $this->addSql('ALTER INDEX idx_f5e3672b5e237e06 RENAME TO IDX_C6A5AA735E237E06');
        $this->addSql('ALTER INDEX idx_f5e3672b17d9eb2 RENAME TO IDX_C6A5AA7317D9EB2');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX idx_c6a5aa7317d9eb2 RENAME TO idx_f5e3672b17d9eb2');
        $this->addSql('ALTER INDEX idx_c6a5aa735e237e06 RENAME TO idx_f5e3672b5e237e06');
        $this->addSql('ALTER INDEX idx_c6a5aa739816d676 RENAME TO idx_f5e3672b9816d676');
        $this->addSql('ALTER INDEX idx_c6a5aa73f92f3e70 RENAME TO idx_f5e3672bf92f3e70');
    }
}
