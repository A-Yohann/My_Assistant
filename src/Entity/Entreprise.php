<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Entreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $idEntreprise;

    #[ORM\Column(type: 'string', length: 100)]
    private $nomEntreprise;

    #[ORM\Column(type: 'string', length: 100)]
    private $siret;

    #[ORM\Column(type: 'string', length: 10)]
    private $telephone;

    #[ORM\Column(type: 'string', length: 50)]
    private $email;

    #[ORM\Column(type: 'boolean')]
    private $formeJuridique;

    #[ORM\Column(type: 'date')]
    private $dateCreation;

    #[ORM\Column(type: 'string', length: 50)]
    private $logo;

    #[ORM\Column(type: 'boolean')]
    private $roles;

    #[ORM\Column(type: 'integer')]
    private $numeroRue;

    #[ORM\Column(type: 'string', length: 100)]
    private $nomRue;

    #[ORM\Column(type: 'string', length: 100)]
    private $complementAdresse;

    #[ORM\Column(type: 'string', length: 10)]
    private $codePostal;

    #[ORM\Column(type: 'string', length: 50)]
    private $ville;

    #[ORM\Column(type: 'string', length: 10)]
    private $pays;

    #[ORM\Column(type: 'boolean')]
    private $type;

    #[ORM\ManyToOne(targetEntity: Siege::class)]
    #[ORM\JoinColumn(name: 'idSiege', referencedColumnName: 'idSiege')]
    private $siege;

    #[ORM\ManyToOne(targetEntity: Dashbord::class)]
    #[ORM\JoinColumn(name: 'idDashbord', referencedColumnName: 'idDashbord')]
    private $dashbord;

    // Getters & setters à ajouter
}
