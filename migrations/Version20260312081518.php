<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312081518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE devis ADD CONSTRAINT FK_8B27C52B8FEDE48A FOREIGN KEY (idEntreprise) REFERENCES entreprise (idEntreprise)');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_D19FA6070C2F7EA FOREIGN KEY (idSiege) REFERENCES siege (idSiege)');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_D19FA60EE675261 FOREIGN KEY (idDashbord) REFERENCES dashbord (idDashbord)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA148FEDE48A FOREIGN KEY (idEntreprise) REFERENCES entreprise (idEntreprise)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE devis DROP FOREIGN KEY FK_8B27C52B8FEDE48A');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_D19FA6070C2F7EA');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_D19FA60EE675261');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA148FEDE48A');
    }
}
