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

namespace App\EventSubscriber;

use App\Constants\Schema\Schema;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

/**
 * Class MigrationEventSubscriber
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-23)
 * @since 0.1.0 (2024-03-23) First version.
 */
class MigrationEventSubscriber implements EventSubscriber
{
    private const POST_GENERATE_SCHEMA = 'postGenerateSchema';

    /**
     * Returns the subscribed events.
     *
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [
            self::POST_GENERATE_SCHEMA
        ];
    }

    /**
     * Ignores creating the schema for the public and topology.
     *
     * @param GenerateSchemaEventArgs $args
     * @return void
     * @throws SchemaException
     */
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        if (! $schema->hasNamespace(Schema::PUBLIC)) {
            $schema->createNamespace(Schema::PUBLIC);
        }

        if (! $schema->hasNamespace(Schema::TOPOLOGY)) {
            $schema->createNamespace(Schema::TOPOLOGY);
        }
    }
}
