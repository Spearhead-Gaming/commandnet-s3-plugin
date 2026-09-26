<?php

declare(strict_types=1);

namespace CommandNetS3PluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925111447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deployment modpacks with versions and their mods.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE s3_mod_pack (name VARCHAR(150) NOT NULL, id INT AUTO_INCREMENT NOT NULL, deployment_id INT NOT NULL, UNIQUE INDEX UNIQ_8BC42F8D9DF4CE98 (deployment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE s3_mod_pack_mod (name VARCHAR(150) NOT NULL, kind VARCHAR(10) NOT NULL, steam_id VARCHAR(20) DEFAULT NULL, position INT NOT NULL, id INT AUTO_INCREMENT NOT NULL, version_id INT NOT NULL, INDEX IDX_A3416D6B4BBC2705 (version_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE s3_mod_pack_version (label VARCHAR(50) NOT NULL, changelog LONGTEXT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, mod_pack_id INT NOT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_76D036E18B8E8428 (created_at), INDEX IDX_76D036E143625D9F (updated_at), INDEX IDX_76D036E1D9D76985 (mod_pack_id), INDEX IDX_76D036E1B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE s3_mod_pack ADD CONSTRAINT FK_8BC42F8D9DF4CE98 FOREIGN KEY (deployment_id) REFERENCES deployment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE s3_mod_pack_mod ADD CONSTRAINT FK_A3416D6B4BBC2705 FOREIGN KEY (version_id) REFERENCES s3_mod_pack_version (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE s3_mod_pack_version ADD CONSTRAINT FK_76D036E1D9D76985 FOREIGN KEY (mod_pack_id) REFERENCES s3_mod_pack (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE s3_mod_pack_version ADD CONSTRAINT FK_76D036E1B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE s3_mod_pack DROP FOREIGN KEY FK_8BC42F8D9DF4CE98');
        $this->addSql('ALTER TABLE s3_mod_pack_mod DROP FOREIGN KEY FK_A3416D6B4BBC2705');
        $this->addSql('ALTER TABLE s3_mod_pack_version DROP FOREIGN KEY FK_76D036E1D9D76985');
        $this->addSql('ALTER TABLE s3_mod_pack_version DROP FOREIGN KEY FK_76D036E1B03A8386');
        $this->addSql('DROP TABLE s3_mod_pack');
        $this->addSql('DROP TABLE s3_mod_pack_mod');
        $this->addSql('DROP TABLE s3_mod_pack_version');
    }
}
