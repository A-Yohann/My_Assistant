<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Dashbord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private $totalRevenu;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private $totalDepense;

    #[ORM\Column(type: 'integer')]
    private $nombreClient;

    #[ORM\Column(type: 'integer')]
    private $nombreDevis;

    #[ORM\Column(type: 'integer')]
    private $nombreFacture;

    public function getId(): ?int
    {
        return $this->id;
    }
}
