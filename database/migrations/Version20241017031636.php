<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241017031636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Example Migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
                id INT AUTO_INCREMENT NOT NULL, 
                username VARCHAR(255) DEFAULT NULL,
                date_added TIMESTAMP NOT NULL,
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
            SQL);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('users');
    }
}
