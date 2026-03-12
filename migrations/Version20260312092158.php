<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312092158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE devis ADD CONSTRAINT FK_8B27C52B8FEDE48A FOREIGN KEY (idEntreprise) REFERENCES entreprise (idEntreprise)');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_D19FA6070C2F7EA FOREIGN KEY (idSiege) REFERENCES siege (id)');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_D19FA60EE675261 FOREIGN KEY (idDashbord) REFERENCES dashbord (idDashbord)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA148FEDE48A FOREIGN KEY (idEntreprise) REFERENCES entreprise (idEntreprise)');
        $this->addSql('ALTER TABLE siege MODIFY id_siege INT NOT NULL');
        $this->addSql('ALTER TABLE siege CHANGE id_siege id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE devis DROP FOREIGN KEY FK_8B27C52B8FEDE48A');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_D19FA6070C2F7EA');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_D19FA60EE675261');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA148FEDE48A');
        $this->addSql('ALTER TABLE siege MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE siege CHANGE id id_siege INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_siege)');
    }
}
