<?php

declare(strict_types=1);

namespace CommandNetS3PluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the S3 briefing table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE s3_briefing (mission_name VARCHAR(255) NOT NULL, task_purpose LONGTEXT NOT NULL, objectives LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, id INT AUTO_INCREMENT NOT NULL, operation_id INT NOT NULL, UNIQUE INDEX UNIQ_C49B184B44AC3583 (operation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE s3_briefing ADD CONSTRAINT FK_S3_BRIEFING_OPERATION FOREIGN KEY (operation_id) REFERENCES operation (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE s3_briefing');
    }
}
