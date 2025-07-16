<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250716143520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE platform_transaction (id INT AUTO_INCREMENT NOT NULL, from_user_id INT NOT NULL, to_user_id INT NOT NULL, reservation_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, status VARCHAR(50) NOT NULL, description VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', processed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3D9003762130303A (from_user_id), INDEX IDX_3D90037629F6EE60 (to_user_id), INDEX IDX_3D900376B83297E7 (reservation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE platform_transaction ADD CONSTRAINT FK_3D9003762130303A FOREIGN KEY (from_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE platform_transaction ADD CONSTRAINT FK_3D90037629F6EE60 FOREIGN KEY (to_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE platform_transaction ADD CONSTRAINT FK_3D900376B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE carshare ADD trip_status VARCHAR(50) DEFAULT NULL, ADD started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD arrived_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE reservation ADD passenger_validated TINYINT(1) NOT NULL, ADD validated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE platform_transaction DROP FOREIGN KEY FK_3D9003762130303A');
        $this->addSql('ALTER TABLE platform_transaction DROP FOREIGN KEY FK_3D90037629F6EE60');
        $this->addSql('ALTER TABLE platform_transaction DROP FOREIGN KEY FK_3D900376B83297E7');
        $this->addSql('DROP TABLE platform_transaction');
        $this->addSql('ALTER TABLE carshare DROP trip_status, DROP started_at, DROP arrived_at');
        $this->addSql('ALTER TABLE reservation DROP passenger_validated, DROP validated_at');
    }
}
