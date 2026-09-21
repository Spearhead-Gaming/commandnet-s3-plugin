<?php

declare(strict_types=1);

namespace CommandNetS3PluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921195006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mission mod lists.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE s3_mission_mod (name VARCHAR(150) NOT NULL, kind VARCHAR(10) NOT NULL, steam_id VARCHAR(20) DEFAULT NULL, position INT NOT NULL, id INT AUTO_INCREMENT NOT NULL, mission_id INT NOT NULL, INDEX IDX_54FFDE18BE6CAE90 (mission_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE s3_mission_mod ADD CONSTRAINT FK_54FFDE18BE6CAE90 FOREIGN KEY (mission_id) REFERENCES s3_mission (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE s3_mission_mod DROP FOREIGN KEY FK_54FFDE18BE6CAE90');
        $this->addSql('DROP TABLE s3_mission_mod');
    }
}
