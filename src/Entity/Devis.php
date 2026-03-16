<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Devis {
    #[ORM\Column(type: 'boolean')]
    private $signature = false;

    public function isSignature(): bool
    {
        return $this->signature;
    }

    public function setSignature(bool $signature): self
    {
        $this->signature = $signature;
        return $this;
    }
        #[ORM\Column(type: 'string', length: 20)]
        private $etat = 'en_attente';

        public function getEtat(): ?string
        {
            return $this->etat;
        }

        public function setEtat(string $etat): self
        {
            $this->etat = $etat;
            return $this;
        }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;
        return $this;
    }
    public function getTauxTVA(): ?float
    {
        return $this->tauxTVA;
    }

    public function setTauxTVA(float $tauxTVA): self
    {
        $this->tauxTVA = $tauxTVA;
        return $this;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
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

    // ...existing code...
// ...existing code...
    public function getMontantTtc(): ?float
    {
        return $this->montantTtc;
    }

    public function setMontantTtc(float $montantTtc): self
    {
        $this->montantTtc = $montantTtc;
        return $this;
    }
    public function getMontantHT(): ?float
    {
        return $this->montantHT;
    }

    public function setMontantHT(float $montantHT): self
    {
        $this->montantHT = $montantHT;
        return $this;
    }
    public function getDateValidite(): ?\DateTimeInterface
    {
        return $this->dateValidite;
    }

    public function setDateValidite(\DateTimeInterface $dateValidite): self
    {
        $this->dateValidite = $dateValidite;
        return $this;
    }
    public function getDateEmission(): ?\DateTimeInterface
    {
        return $this->dateEmission;
    }

    public function setDateEmission(\DateTimeInterface $dateEmission): self
    {
        $this->dateEmission = $dateEmission;
        return $this;
    }
    public function getNumeroDevis(): ?string
    {
        return $this->numeroDevis;
    }

    public function setNumeroDevis(string $numeroDevis): self
    {
        $this->numeroDevis = $numeroDevis;
        return $this;
    }
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

    // ...existing code...

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(name: 'entreprise_id', referencedColumnName: 'id')]
    private $entreprise;

    // Getters & setters à ajouter
}
