<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260317132540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bon_de_commande ADD CONSTRAINT FK_2C3802E4A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE bon_de_commande ADD CONSTRAINT FK_2C3802E441DEFADA FOREIGN KEY (devis_id) REFERENCES devis (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE depense_budgetaire ADD CONSTRAINT FK_7C936380A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE devis ADD CONSTRAINT FK_8B27C52BA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE devis ADD CONSTRAINT FK_8B27C52B19EB6921 FOREIGN KEY (client_id) REFERENCES client (id_client)');
        $this->addSql('ALTER TABLE entreprise CHANGE tva tva NUMERIC(5, 2) DEFAULT 0.2 NOT NULL');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_D19FA6070C2F7EA FOREIGN KEY (idSiege) REFERENCES siege (id)');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_D19FA60EE675261 FOREIGN KEY (idDashbord) REFERENCES dashbord (id)');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_D19FA60A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE866410A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE866410D29E677C FOREIGN KEY (bon_de_commande_id) REFERENCES bon_de_commande (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bon_de_commande DROP FOREIGN KEY FK_2C3802E4A4AEAFEA');
        $this->addSql('ALTER TABLE bon_de_commande DROP FOREIGN KEY FK_2C3802E441DEFADA');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455A76ED395');
        $this->addSql('ALTER TABLE depense_budgetaire DROP FOREIGN KEY FK_7C936380A76ED395');
        $this->addSql('ALTER TABLE devis DROP FOREIGN KEY FK_8B27C52BA4AEAFEA');
        $this->addSql('ALTER TABLE devis DROP FOREIGN KEY FK_8B27C52B19EB6921');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_D19FA6070C2F7EA');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_D19FA60EE675261');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_D19FA60A76ED395');
        $this->addSql('ALTER TABLE entreprise CHANGE tva tva NUMERIC(5, 2) DEFAULT \'0.20\' NOT NULL');
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE866410A4AEAFEA');
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE866410D29E677C');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14A4AEAFEA');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14A76ED395');
    }
}
