<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class RendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $idRdv;

    #[ORM\Column(type: 'string', length: 50)]
    private $titre;

    #[ORM\Column(type: 'datetime')]
    private $dateDebut;

    #[ORM\Column(type: 'datetime')]
    private $dateFin;

    #[ORM\Column(type: 'string', length: 200)]
    private $lieu;

    #[ORM\Column(type: 'text')]
    private $description;

    #[ORM\Column(type: 'boolean')]
    private $statu;

    #[ORM\Column(type: 'date')]
    private $dateCreation;

    // Getters & setters à ajouter
}
