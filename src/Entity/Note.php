<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $idNote;

    #[ORM\Column(type: 'string', length: 100)]
    private $titre;

    #[ORM\Column(type: 'text')]
    private $contenu;

    #[ORM\Column(type: 'datetime')]
    private $dateCreation;

    #[ORM\Column(type: 'datetime')]
    private $dateModification;

    #[ORM\Column(type: 'boolean')]
    private $priorite;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(name: 'idEntreprise', referencedColumnName: 'idEntreprise')]
    private $entreprise;

    // Getters & setters à ajouter
}
