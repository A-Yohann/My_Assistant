<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Siege
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 50)]
    private $nomSiege;

    #[ORM\Column(type: 'string', length: 100)]
    private $AddresseSiege;

    #[ORM\Column(type: 'date')]
    private $dateCreation;

    #[ORM\Column(type: 'boolean')]
    private $statuJuridique;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomSiege(): ?string
    {
        return $this->nomSiege;
    }
    public function setNomSiege(string $nomSiege): self
    {
        $this->nomSiege = $nomSiege;
        return $this;
    }

    public function getAddresseSiege(): ?string
    {
        return $this->AddresseSiege;
    }
    public function setAddresseSiege(string $AddresseSiege): self
    {
        $this->AddresseSiege = $AddresseSiege;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }
    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function isStatuJuridique(): ?bool
    {
        return $this->statuJuridique;
    }
    public function setStatuJuridique(bool $statuJuridique): self
    {
        $this->statuJuridique = $statuJuridique;
        return $this;
    }
}
