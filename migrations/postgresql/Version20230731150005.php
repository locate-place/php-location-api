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
 * Class Version20230731150005
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 */
final class Version20230731150005 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Sets all values of location.coordinate field to NULL. The field coordinate_geography of table location is not allowed to be NULL.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ALTER coordinate DROP NOT NULL');
        $this->addSql('ALTER TABLE location ALTER coordinate_geography SET NOT NULL');
        /* Data loss! Sets all values of location.coordinate field to NULL. */
        $this->addSql('UPDATE location SET coordinate = NULL;');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ALTER coordinate SET NOT NULL');
        $this->addSql('ALTER TABLE location ALTER coordinate_geography DROP NOT NULL');
    }
}
