<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221217015633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE access_token (id UUID NOT NULL, type VARCHAR(255) NOT NULL, token VARCHAR(255) NOT NULL, expires INT DEFAULT NULL, refresh_token VARCHAR(255) DEFAULT NULL, values TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN access_token.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN access_token.values IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, mastodon_access_token_id UUID DEFAULT NULL, roles JSON NOT NULL, username VARCHAR(255) NOT NULL, mastodon_id VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6497A72805D ON "user" (mastodon_access_token_id)');
        $this->addSql('CREATE INDEX mastodon_id_idx ON "user" (mastodon_id)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN "user".mastodon_access_token_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D6497A72805D FOREIGN KEY (mastodon_access_token_id) REFERENCES access_token (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D6497A72805D');
        $this->addSql('DROP TABLE access_token');
        $this->addSql('DROP TABLE "user"');
    }
}
