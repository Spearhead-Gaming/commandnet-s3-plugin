<?php

declare(strict_types=1);

namespace CommandNetS3PluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921183201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the mission repository, playtest feedback and live mission notes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE s3_mission (name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, operation_id INT DEFAULT NULL, INDEX IDX_684566D27E3C61F9 (owner_id), INDEX IDX_684566D244AC3583 (operation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE s3_mission_feedback (kind VARCHAR(20) NOT NULL, description LONGTEXT NOT NULL, resolved TINYINT NOT NULL, id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, version_id INT NOT NULL, author_id INT DEFAULT NULL, resolved_by_id INT DEFAULT NULL, INDEX IDX_220816528B8E8428 (created_at), INDEX IDX_2208165243625D9F (updated_at), INDEX IDX_220816524BBC2705 (version_id), INDEX IDX_22081652F675F31B (author_id), INDEX IDX_220816526713A32B (resolved_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE s3_mission_note (text LONGTEXT NOT NULL, id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, operation_id INT NOT NULL, author_id INT DEFAULT NULL, INDEX IDX_A495A06D8B8E8428 (created_at), INDEX IDX_A495A06D43625D9F (updated_at), INDEX IDX_A495A06D44AC3583 (operation_id), INDEX IDX_A495A06DF675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE s3_mission_version (label VARCHAR(50) NOT NULL, notes LONGTEXT DEFAULT NULL, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(64) NOT NULL, size BIGINT NOT NULL, feedback_token VARCHAR(32) NOT NULL, id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, mission_id INT NOT NULL, uploaded_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_91DD9D53CB96385E (feedback_token), INDEX IDX_91DD9D538B8E8428 (created_at), INDEX IDX_91DD9D5343625D9F (updated_at), INDEX IDX_91DD9D53BE6CAE90 (mission_id), INDEX IDX_91DD9D53A2B28FE8 (uploaded_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE s3_mission ADD CONSTRAINT FK_684566D27E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE s3_mission ADD CONSTRAINT FK_684566D244AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE s3_mission_feedback ADD CONSTRAINT FK_220816524BBC2705 FOREIGN KEY (version_id) REFERENCES s3_mission_version (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE s3_mission_feedback ADD CONSTRAINT FK_22081652F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE s3_mission_feedback ADD CONSTRAINT FK_220816526713A32B FOREIGN KEY (resolved_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE s3_mission_note ADD CONSTRAINT FK_A495A06D44AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE s3_mission_note ADD CONSTRAINT FK_A495A06DF675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE s3_mission_version ADD CONSTRAINT FK_91DD9D53BE6CAE90 FOREIGN KEY (mission_id) REFERENCES s3_mission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE s3_mission_version ADD CONSTRAINT FK_91DD9D53A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE s3_mission DROP FOREIGN KEY FK_684566D27E3C61F9');
        $this->addSql('ALTER TABLE s3_mission DROP FOREIGN KEY FK_684566D244AC3583');
        $this->addSql('ALTER TABLE s3_mission_feedback DROP FOREIGN KEY FK_220816524BBC2705');
        $this->addSql('ALTER TABLE s3_mission_feedback DROP FOREIGN KEY FK_22081652F675F31B');
        $this->addSql('ALTER TABLE s3_mission_feedback DROP FOREIGN KEY FK_220816526713A32B');
        $this->addSql('ALTER TABLE s3_mission_note DROP FOREIGN KEY FK_A495A06D44AC3583');
        $this->addSql('ALTER TABLE s3_mission_note DROP FOREIGN KEY FK_A495A06DF675F31B');
        $this->addSql('ALTER TABLE s3_mission_version DROP FOREIGN KEY FK_91DD9D53BE6CAE90');
        $this->addSql('ALTER TABLE s3_mission_version DROP FOREIGN KEY FK_91DD9D53A2B28FE8');
        $this->addSql('DROP TABLE s3_mission');
        $this->addSql('DROP TABLE s3_mission_feedback');
        $this->addSql('DROP TABLE s3_mission_note');
        $this->addSql('DROP TABLE s3_mission_version');
    }
}
