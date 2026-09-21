<?php

declare(strict_types=1);

namespace CommandNetS3PluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921162935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the SOP/doctrine library tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE s3_sop (title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE s3_sop_acknowledgement (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, sop_version_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_5A96B7F88B8E8428 (created_at), INDEX IDX_5A96B7F843625D9F (updated_at), UNIQUE INDEX s3_sop_ack_unique (sop_version_id, user_id), INDEX IDX_5A96B7F890C0757 (sop_version_id), INDEX IDX_5A96B7F8A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE s3_sop_version (label VARCHAR(50) NOT NULL, content LONGTEXT NOT NULL, changelog LONGTEXT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, sop_id INT NOT NULL, INDEX IDX_AA10EEC88B8E8428 (created_at), INDEX IDX_AA10EEC843625D9F (updated_at), INDEX IDX_AA10EEC8D52982EE (sop_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE s3_sop_acknowledgement ADD CONSTRAINT FK_5A96B7F890C0757 FOREIGN KEY (sop_version_id) REFERENCES s3_sop_version (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE s3_sop_acknowledgement ADD CONSTRAINT FK_5A96B7F8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE s3_sop_version ADD CONSTRAINT FK_AA10EEC8D52982EE FOREIGN KEY (sop_id) REFERENCES s3_sop (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE s3_sop_acknowledgement DROP FOREIGN KEY FK_5A96B7F890C0757');
        $this->addSql('ALTER TABLE s3_sop_acknowledgement DROP FOREIGN KEY FK_5A96B7F8A76ED395');
        $this->addSql('ALTER TABLE s3_sop_version DROP FOREIGN KEY FK_AA10EEC8D52982EE');
        $this->addSql('DROP TABLE s3_sop');
        $this->addSql('DROP TABLE s3_sop_acknowledgement');
        $this->addSql('DROP TABLE s3_sop_version');
    }
}
