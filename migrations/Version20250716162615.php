<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250716162615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, driver_id INT NOT NULL, passenger_id INT NOT NULL, carshare_id INT NOT NULL, reservation_id INT NOT NULL, moderated_by_id INT DEFAULT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', moderated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_794381C6C3423909 (driver_id), INDEX IDX_794381C64502E565 (passenger_id), INDEX IDX_794381C6D05257A (carshare_id), INDEX IDX_794381C6B83297E7 (reservation_id), INDEX IDX_794381C68EDA19B0 (moderated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6C3423909 FOREIGN KEY (driver_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C64502E565 FOREIGN KEY (passenger_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6D05257A FOREIGN KEY (carshare_id) REFERENCES carshare (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C68EDA19B0 FOREIGN KEY (moderated_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6C3423909');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C64502E565');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6D05257A');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6B83297E7');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C68EDA19B0');
        $this->addSql('DROP TABLE review');
    }
}
