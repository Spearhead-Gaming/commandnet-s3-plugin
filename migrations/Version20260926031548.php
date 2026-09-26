<?php

declare(strict_types=1);

namespace CommandNetS3PluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926031548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the details operation pages share per Deployment.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE s3_deployment_page (steam_collection_url VARCHAR(500) DEFAULT NULL, comms LONGTEXT DEFAULT NULL, roe LONGTEXT DEFAULT NULL, checklist LONGTEXT DEFAULT NULL, quick_links LONGTEXT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, deployment_id INT NOT NULL, server_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_7DE083A9DF4CE98 (deployment_id), INDEX IDX_7DE083A1844E6B7 (server_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE s3_deployment_page ADD CONSTRAINT FK_7DE083A9DF4CE98 FOREIGN KEY (deployment_id) REFERENCES deployment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE s3_deployment_page ADD CONSTRAINT FK_7DE083A1844E6B7 FOREIGN KEY (server_id) REFERENCES s3_game_server (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE s3_deployment_page DROP FOREIGN KEY FK_7DE083A9DF4CE98');
        $this->addSql('ALTER TABLE s3_deployment_page DROP FOREIGN KEY FK_7DE083A1844E6B7');
        $this->addSql('DROP TABLE s3_deployment_page');
    }
}
