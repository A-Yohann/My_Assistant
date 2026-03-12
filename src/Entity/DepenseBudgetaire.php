<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DepenseBudgetaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $idDepense;

    #[ORM\Column(type: 'string', length: 100)]
    private $libelle;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private $montant;

    #[ORM\Column(type: 'date')]
    private $dateDepense;

    #[ORM\Column(type: 'boolean')]
    private $moyenPaiement;

    #[ORM\Column(type: 'text')]
    private $justificatif;

    #[ORM\Column(type: 'integer')]
    private $quantite;

    // Getters & setters à ajouter
}
