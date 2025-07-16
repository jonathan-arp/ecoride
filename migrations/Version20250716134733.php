<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250716134733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE credit (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, carshare_id INT DEFAULT NULL, amount DOUBLE PRECISION NOT NULL, type VARCHAR(50) NOT NULL, description VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1CC16EFEA76ED395 (user_id), INDEX IDX_1CC16EFED05257A (carshare_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, passenger_id INT NOT NULL, carshare_id INT NOT NULL, price DOUBLE PRECISION NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_42C849554502E565 (passenger_id), INDEX IDX_42C84955D05257A (carshare_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit ADD CONSTRAINT FK_1CC16EFEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE credit ADD CONSTRAINT FK_1CC16EFED05257A FOREIGN KEY (carshare_id) REFERENCES carshare (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849554502E565 FOREIGN KEY (passenger_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955D05257A FOREIGN KEY (carshare_id) REFERENCES carshare (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit DROP FOREIGN KEY FK_1CC16EFEA76ED395');
        $this->addSql('ALTER TABLE credit DROP FOREIGN KEY FK_1CC16EFED05257A');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849554502E565');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955D05257A');
        $this->addSql('DROP TABLE credit');
        $this->addSql('DROP TABLE reservation');
    }
}
