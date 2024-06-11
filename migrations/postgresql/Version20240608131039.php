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
 * Class Version20240608131039
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-08)
 * @since 0.1.0 (2024-06-08) First version.
 */
final class Version20240608131039 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adds all api tables to log credentials and limits.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_endpoint (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, endpoint VARCHAR(255) NOT NULL, method VARCHAR(14) DEFAULT NULL, credits INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1779C5F4C4420F7B5E593A60 ON api_endpoint (endpoint, method)');
        $this->addSql('CREATE TABLE api_key (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, api_key VARCHAR(32) NOT NULL, is_public BOOLEAN NOT NULL, has_ip_limit BOOLEAN NOT NULL, has_credential_limit BOOLEAN NOT NULL, limits_per_minute INT DEFAULT NULL, limits_per_hour INT DEFAULT NULL, credits_per_day INT DEFAULT NULL, credits_per_month INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C912ED9DC912ED9D ON api_key (api_key)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C912ED9DC912ED9D ON api_key (api_key)');
        $this->addSql('CREATE TABLE api_key_credits_day (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, credits_used INT NOT NULL, day DATE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, api_key_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DC6B76388BE312B3 ON api_key_credits_day (api_key_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DC6B76388BE312B3E5A02990 ON api_key_credits_day (api_key_id, day)');
        $this->addSql('CREATE TABLE api_key_credits_month (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, credits_used INT NOT NULL, month DATE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, api_key_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_13BC53498BE312B3 ON api_key_credits_month (api_key_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_13BC53498BE312B38EB61006 ON api_key_credits_month (api_key_id, month)');
        $this->addSql('CREATE TABLE api_request_log (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, credits_used INT NOT NULL, ip VARCHAR(45) NOT NULL, browser TEXT NOT NULL, referrer TEXT NOT NULL, is_valid BOOLEAN NOT NULL, given JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, api_key_id INT NOT NULL, api_request_log_type_id INT NOT NULL, api_endpoint_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2862A307790F6790 ON api_request_log (api_request_log_type_id)');
        $this->addSql('CREATE INDEX IDX_2862A3074BD8F4B8 ON api_request_log (api_endpoint_id)');
        $this->addSql('CREATE INDEX IDX_2862A3078BE312B3 ON api_request_log (api_key_id)');
        $this->addSql('CREATE INDEX IDX_2862A3078BE312B38B8E8428 ON api_request_log (api_key_id, created_at)');
        $this->addSql('CREATE INDEX IDX_2862A3078BE312B38B8E8428790F6790 ON api_request_log (api_key_id, created_at, api_request_log_type_id)');
        $this->addSql('CREATE INDEX IDX_2862A3078BE312B3790F6790 ON api_request_log (api_key_id, api_request_log_type_id)');
        $this->addSql('CREATE INDEX IDX_2862A307790F67908B8E8428 ON api_request_log (api_request_log_type_id, created_at)');
        $this->addSql('CREATE TABLE api_request_log_type (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, type VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2A0BFC2D8CDE5729 ON api_request_log_type (type)');
        $this->addSql('ALTER TABLE api_key_credits_day ADD CONSTRAINT FK_DC6B76388BE312B3 FOREIGN KEY (api_key_id) REFERENCES api_key (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE api_key_credits_month ADD CONSTRAINT FK_13BC53498BE312B3 FOREIGN KEY (api_key_id) REFERENCES api_key (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE api_request_log ADD CONSTRAINT FK_2862A3078BE312B3 FOREIGN KEY (api_key_id) REFERENCES api_key (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE api_request_log ADD CONSTRAINT FK_2862A307790F6790 FOREIGN KEY (api_request_log_type_id) REFERENCES api_request_log_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE api_request_log ADD CONSTRAINT FK_2862A3074BD8F4B8 FOREIGN KEY (api_endpoint_id) REFERENCES api_endpoint (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_key_credits_day DROP CONSTRAINT FK_DC6B76388BE312B3');
        $this->addSql('ALTER TABLE api_key_credits_month DROP CONSTRAINT FK_13BC53498BE312B3');
        $this->addSql('ALTER TABLE api_request_log DROP CONSTRAINT FK_2862A3078BE312B3');
        $this->addSql('ALTER TABLE api_request_log DROP CONSTRAINT FK_2862A307790F6790');
        $this->addSql('ALTER TABLE api_request_log DROP CONSTRAINT FK_2862A3074BD8F4B8');
        $this->addSql('DROP TABLE api_endpoint');
        $this->addSql('DROP TABLE api_key');
        $this->addSql('DROP TABLE api_key_credits_day');
        $this->addSql('DROP TABLE api_key_credits_month');
        $this->addSql('DROP TABLE api_request_log');
        $this->addSql('DROP TABLE api_request_log_type');
    }
}