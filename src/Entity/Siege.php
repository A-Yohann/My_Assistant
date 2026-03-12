<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Siege
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $idSiege;

    #[ORM\Column(type: 'string', length: 50)]
    private $nomSiege;

    #[ORM\Column(type: 'string', length: 100)]
    private $AddresseSiege;

    #[ORM\Column(type: 'date')]
    private $dateCreation;

    #[ORM\Column(type: 'boolean')]
    private $statuJuridique;

    // Getters & setters à ajouter
}
