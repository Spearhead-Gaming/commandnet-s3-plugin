<?php

declare(strict_types=1);

namespace CommandNetS3PluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921172027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mission-specific kit approvals.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE s3_mission_kit_approval (notes LONGTEXT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, operation_id INT NOT NULL, equipment_id INT NOT NULL, UNIQUE INDEX s3_mission_kit_unique (operation_id, equipment_id), INDEX IDX_19A0BA9644AC3583 (operation_id), INDEX IDX_19A0BA96517FE9FE (equipment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE s3_mission_kit_approval ADD CONSTRAINT FK_19A0BA9644AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE s3_mission_kit_approval ADD CONSTRAINT FK_19A0BA96517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE s3_mission_kit_approval DROP FOREIGN KEY FK_19A0BA9644AC3583');
        $this->addSql('ALTER TABLE s3_mission_kit_approval DROP FOREIGN KEY FK_19A0BA96517FE9FE');
        $this->addSql('DROP TABLE s3_mission_kit_approval');
    }
}
