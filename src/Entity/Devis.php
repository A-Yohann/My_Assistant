<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Devis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 50)]
    private $numeroDevis;

    #[ORM\Column(type: 'date')]
    private $dateEmission;

    #[ORM\Column(type: 'date')]
    private $dateValidite;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private $montantHT;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private $montantTtc;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private $tauxTVA;

    #[ORM\Column(type: 'boolean')]
    private $status;

    #[ORM\Column(type: 'text')]
    private $description;

    #[ORM\Column(type: 'date')]
    private $dateCreation;

    #[ORM\Column(type: 'boolean')]
    private $signature;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(name: 'entreprise_id', referencedColumnName: 'id')]
    private $entreprise;

    // Getters & setters à ajouter
}
