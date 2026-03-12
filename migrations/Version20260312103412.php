<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312103412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE devis ADD CONSTRAINT FK_8B27C52B8FEDE48A FOREIGN KEY (idEntreprise) REFERENCES entreprise (idEntreprise)');
        // $this->addSql('ALTER TABLE entreprise ADD user_id INT NOT NULL'); // colonne déjà existante
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_D19FA6070C2F7EA FOREIGN KEY (idSiege) REFERENCES siege (id)');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_D19FA60EE675261 FOREIGN KEY (idDashbord) REFERENCES dashbord (id)');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_D19FA60A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        // $this->addSql('CREATE INDEX IDX_D19FA60A76ED395 ON entreprise (user_id)'); // index déjà existant
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA148FEDE48A FOREIGN KEY (idEntreprise) REFERENCES entreprise (idEntreprise)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE devis DROP FOREIGN KEY FK_8B27C52B8FEDE48A');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_D19FA6070C2F7EA');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_D19FA60EE675261');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_D19FA60A76ED395');
        $this->addSql('DROP INDEX IDX_D19FA60A76ED395 ON entreprise');
        $this->addSql('ALTER TABLE entreprise DROP user_id');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA148FEDE48A');
    }
}
