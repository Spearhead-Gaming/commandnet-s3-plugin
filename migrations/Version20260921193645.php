<?php

declare(strict_types=1);

namespace CommandNetS3PluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921193645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the operation information page content table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE s3_operation_page (published TINYINT NOT NULL, order_number VARCHAR(30) DEFAULT NULL, summary VARCHAR(500) DEFAULT NULL, preset_url VARCHAR(500) DEFAULT NULL, steam_collection_url VARCHAR(500) DEFAULT NULL, timeline LONGTEXT DEFAULT NULL, task_org LONGTEXT DEFAULT NULL, mission_data LONGTEXT DEFAULT NULL, comms LONGTEXT DEFAULT NULL, roe LONGTEXT DEFAULT NULL, checklist LONGTEXT DEFAULT NULL, quick_links LONGTEXT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, operation_id INT NOT NULL, server_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_16C1208244AC3583 (operation_id), INDEX IDX_16C120821844E6B7 (server_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE s3_operation_page ADD CONSTRAINT FK_16C1208244AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE s3_operation_page ADD CONSTRAINT FK_16C120821844E6B7 FOREIGN KEY (server_id) REFERENCES s3_game_server (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE s3_operation_page DROP FOREIGN KEY FK_16C1208244AC3583');
        $this->addSql('ALTER TABLE s3_operation_page DROP FOREIGN KEY FK_16C120821844E6B7');
        $this->addSql('DROP TABLE s3_operation_page');
    }
}
