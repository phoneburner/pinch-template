<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250000000000 extends AbstractMigration
{
    public const string DEFAULT_FAILED_MESSAGE_TABLE = 'message_bus_failed_messages';

    public function getDescription(): string
    {
        return 'Add the table needed to track messages that were unable to be successfully processed by the message bus';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(\sprintf(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `%s`
            (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `body` TEXT NOT NULL,
                `headers` TEXT NOT NULL,
                `queue_name` VARCHAR(190) NOT NULL,
                `created_at` TIMESTAMP NOT NULL,
                `available_at` TIMESTAMP NOT NULL,
                `delivered_at` TIMESTAMP NULL,
                PRIMARY KEY (`id` ASC),
                INDEX (`queue_name`),
                INDEX `available_at` (`available_at`),
                INDEX `delivered_at` (`delivered_at`)
            ) DEFAULT CHARACTER SET utf8mb4
              COLLATE utf8mb4_general_ci
              ENGINE = InnoDB;
            SQL, self::DEFAULT_FAILED_MESSAGE_TABLE));
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(self::DEFAULT_FAILED_MESSAGE_TABLE);
    }
}
