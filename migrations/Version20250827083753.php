<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250827083753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE city CHANGE name name VARCHAR(150) NOT NULL, CHANGE postal_code postal_code VARCHAR(12) NOT NULL');
        $this->addSql('ALTER TABLE place DROP FOREIGN KEY FK_741D53CD8BAC62AF');
        $this->addSql('ALTER TABLE place CHANGE city_id city_id INT NOT NULL, CHANGE name name VARCHAR(150) NOT NULL, CHANGE gps_latitude gps_latitude DOUBLE PRECISION DEFAULT NULL, CHANGE gps_longitude gps_longitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE place ADD CONSTRAINT FK_741D53CD8BAC62AF FOREIGN KEY (city_id) REFERENCES city (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE city CHANGE name name VARCHAR(255) NOT NULL, CHANGE postal_code postal_code VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE place DROP FOREIGN KEY FK_741D53CD8BAC62AF');
        $this->addSql('ALTER TABLE place CHANGE city_id city_id INT DEFAULT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE gps_latitude gps_latitude DOUBLE PRECISION NOT NULL, CHANGE gps_longitude gps_longitude DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE place ADD CONSTRAINT FK_741D53CD8BAC62AF FOREIGN KEY (city_id) REFERENCES city (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
