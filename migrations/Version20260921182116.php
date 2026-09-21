<?php

declare(strict_types=1);

namespace CommandNetS3PluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921182116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game servers and the mod version tracker.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE s3_game_server (name VARCHAR(100) NOT NULL, host VARCHAR(255) NOT NULL, query_port INT NOT NULL, id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE s3_server_mod (name VARCHAR(150) NOT NULL, workshop_id VARCHAR(30) DEFAULT NULL, installed_version VARCHAR(50) NOT NULL, modpack_version VARCHAR(50) DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, server_id INT NOT NULL, INDEX IDX_4A0CAABA1844E6B7 (server_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE s3_server_mod ADD CONSTRAINT FK_4A0CAABA1844E6B7 FOREIGN KEY (server_id) REFERENCES s3_game_server (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE s3_server_mod DROP FOREIGN KEY FK_4A0CAABA1844E6B7');
        $this->addSql('DROP TABLE s3_game_server');
        $this->addSql('DROP TABLE s3_server_mod');
    }
}
