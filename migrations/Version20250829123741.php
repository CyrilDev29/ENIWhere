<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250829123741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA75D83CC1');
        $this->addSql('DROP INDEX IDX_3BAE0AA75D83CC1 ON event');
        $this->addSql('ALTER TABLE event ADD state VARCHAR(20) NOT NULL, DROP state_id, DROP status');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD state_id INT DEFAULT NULL, ADD status VARCHAR(30) NOT NULL, DROP state');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA75D83CC1 FOREIGN KEY (state_id) REFERENCES state (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_3BAE0AA75D83CC1 ON event (state_id)');
    }
}
