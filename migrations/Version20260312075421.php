<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312075421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE dashbord');
        $this->addSql('DROP TABLE depense_budgetaire');
        $this->addSql('DROP TABLE devis');
        $this->addSql('DROP TABLE entreprise');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE rendez_vous');
        $this->addSql('DROP TABLE siege');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id_client INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, prenom VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, email VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, telephone VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, date_creation DATE NOT NULL, PRIMARY KEY (id_client)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE dashbord (id_dashbord INT AUTO_INCREMENT NOT NULL, total_revenu NUMERIC(10, 2) NOT NULL, total_depense NUMERIC(10, 2) NOT NULL, nombre_client INT NOT NULL, nombre_devis INT NOT NULL, nombre_facture INT NOT NULL, PRIMARY KEY (id_dashbord)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE depense_budgetaire (id_depense INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, montant NUMERIC(10, 2) NOT NULL, date_depense DATE NOT NULL, moyen_paiement TINYINT NOT NULL, justificatif LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, quantite INT NOT NULL, PRIMARY KEY (id_depense)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE devis (id INT AUTO_INCREMENT NOT NULL, numero_devis VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, date_emission DATE NOT NULL, date_validite DATE NOT NULL, montant_ht NUMERIC(10, 2) NOT NULL, montant_ttc NUMERIC(10, 2) NOT NULL, taux_tva NUMERIC(5, 2) NOT NULL, status TINYINT NOT NULL, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, date_creation DATE NOT NULL, signature TINYINT NOT NULL, idEntreprise INT DEFAULT NULL, INDEX IDX_8B27C52B8FEDE48A (idEntreprise), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE entreprise (id_entreprise INT AUTO_INCREMENT NOT NULL, nom_entreprise VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, siret VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, telephone VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, email VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, forme_juridique TINYINT NOT NULL, date_creation DATE NOT NULL, logo VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, roles TINYINT NOT NULL, numero_rue INT NOT NULL, nom_rue VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, complement_adresse VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, code_postal VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, ville VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, pays VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, type TINYINT NOT NULL, idSiege INT DEFAULT NULL, idDashbord INT DEFAULT NULL, INDEX IDX_D19FA6070C2F7EA (idSiege), INDEX IDX_D19FA60EE675261 (idDashbord), PRIMARY KEY (id_entreprise)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE note (id_note INT AUTO_INCREMENT NOT NULL, titre VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, contenu LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, date_creation DATETIME NOT NULL, date_modification DATETIME NOT NULL, priorite TINYINT NOT NULL, idEntreprise INT DEFAULT NULL, INDEX IDX_CFBDFA148FEDE48A (idEntreprise), PRIMARY KEY (id_note)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rendez_vous (id_rdv INT AUTO_INCREMENT NOT NULL, titre VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, lieu VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, statu TINYINT NOT NULL, date_creation DATE NOT NULL, PRIMARY KEY (id_rdv)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE siege (id_siege INT AUTO_INCREMENT NOT NULL, nom_siege VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, addresse_siege VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, date_creation DATE NOT NULL, statu_juridique TINYINT NOT NULL, PRIMARY KEY (id_siege)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = MyISAM COMMENT = \'\' ');
    }
}
