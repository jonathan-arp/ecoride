<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table des sessions
 */
final class Version20250717000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Créer la table sessions pour le stockage des sessions en base de données';
    }

    public function up(Schema $schema): void
    {
        // Créer la table sessions pour Symfony
        $this->addSql('CREATE TABLE sessions (
            sess_id VARCHAR(128) NOT NULL PRIMARY KEY,
            sess_data LONGTEXT NOT NULL,
            sess_lifetime INTEGER UNSIGNED NOT NULL,
            sess_time INTEGER UNSIGNED NOT NULL,
            INDEX sessions_sess_lifetime_idx (sess_lifetime)
        ) COLLATE utf8mb4_bin, ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sessions');
    }
}
