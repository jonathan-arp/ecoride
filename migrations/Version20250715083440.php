<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250715083440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE brand (id INT AUTO_INCREMENT NOT NULL, content VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE car (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, brand_id INT NOT NULL, model VARCHAR(255) NOT NULL, matriculation VARCHAR(255) NOT NULL, energy_type VARCHAR(255) NOT NULL, color VARCHAR(255) NOT NULL, date_first_matricule DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', INDEX IDX_773DE69DA76ED395 (user_id), INDEX IDX_773DE69D44F5D008 (brand_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE carshare (id INT AUTO_INCREMENT NOT NULL, driver_id INT NOT NULL, car_id INT NOT NULL, start DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', end DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', start_location VARCHAR(255) NOT NULL, end_location VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, place INT DEFAULT NULL, price DOUBLE PRECISION NOT NULL, INDEX IDX_7949F9EDC3423909 (driver_id), INDEX IDX_7949F9EDC3C6F69F (car_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parameter (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, icon VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_parameters (user_id INT NOT NULL, parameter_id INT NOT NULL, INDEX IDX_A1F48E12A76ED395 (user_id), INDEX IDX_A1F48E127C56DBD6 (parameter_id), PRIMARY KEY(user_id, parameter_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE car ADD CONSTRAINT FK_773DE69DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE car ADD CONSTRAINT FK_773DE69D44F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id)');
        $this->addSql('ALTER TABLE carshare ADD CONSTRAINT FK_7949F9EDC3423909 FOREIGN KEY (driver_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE carshare ADD CONSTRAINT FK_7949F9EDC3C6F69F FOREIGN KEY (car_id) REFERENCES car (id)');
        $this->addSql('ALTER TABLE user_parameters ADD CONSTRAINT FK_A1F48E12A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_parameters ADD CONSTRAINT FK_A1F48E127C56DBD6 FOREIGN KEY (parameter_id) REFERENCES parameter (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE car DROP FOREIGN KEY FK_773DE69DA76ED395');
        $this->addSql('ALTER TABLE car DROP FOREIGN KEY FK_773DE69D44F5D008');
        $this->addSql('ALTER TABLE carshare DROP FOREIGN KEY FK_7949F9EDC3423909');
        $this->addSql('ALTER TABLE carshare DROP FOREIGN KEY FK_7949F9EDC3C6F69F');
        $this->addSql('ALTER TABLE user_parameters DROP FOREIGN KEY FK_A1F48E12A76ED395');
        $this->addSql('ALTER TABLE user_parameters DROP FOREIGN KEY FK_A1F48E127C56DBD6');
        $this->addSql('DROP TABLE brand');
        $this->addSql('DROP TABLE car');
        $this->addSql('DROP TABLE carshare');
        $this->addSql('DROP TABLE parameter');
        $this->addSql('DROP TABLE user_parameters');
    }
}
