<?php

declare(strict_types=1);

namespace CommandNetS3PluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921164407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the Zeus asset library table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE s3_zeus_asset (name VARCHAR(255) NOT NULL, category VARCHAR(20) NOT NULL, mod_url VARCHAR(500) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE s3_zeus_asset');
    }
}
