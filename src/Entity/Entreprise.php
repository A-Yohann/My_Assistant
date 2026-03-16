<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Siege;
use App\Entity\Dashbord;

#[ORM\Entity]
class Entreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 100)]
    private $nomEntreprise;

    #[ORM\Column(type: 'string', length: 100)]
    private $siret;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
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

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $status;

    #[ORM\ManyToOne(targetEntity: Siege::class)]
    #[ORM\JoinColumn(name: 'idSiege', referencedColumnName: 'id')]
    private $siege;

    #[ORM\ManyToOne(targetEntity: Dashbord::class)]
    #[ORM\JoinColumn(name: 'idDashbord', referencedColumnName: 'id')]
    private $dashbord;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private $user;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    // Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomEntreprise(): ?string
    {
        return $this->nomEntreprise;
    }

    public function setNomEntreprise(string $nomEntreprise): self
    {
        $this->nomEntreprise = $nomEntreprise;
        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): self
    {
        $this->siret = $siret;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getFormeJuridique(): ?bool
    {
        return $this->formeJuridique;
    }

    public function setFormeJuridique(bool $formeJuridique): self
    {
        $this->formeJuridique = $formeJuridique;
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

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): self
    {
        $this->logo = $logo;
        return $this;
    }

    public function getRoles(): ?bool
    {
        return $this->roles;
    }

    public function setRoles(bool $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getNumeroRue(): ?int
    {
        return $this->numeroRue;
    }

    public function setNumeroRue(int $numeroRue): self
    {
        $this->numeroRue = $numeroRue;
        return $this;
    }

    public function getNomRue(): ?string
    {
        return $this->nomRue;
    }

    public function setNomRue(string $nomRue): self
    {
        $this->nomRue = $nomRue;
        return $this;
    }

    public function getComplementAdresse(): ?string
    {
        return $this->complementAdresse;
    }

    public function setComplementAdresse(?string $complementAdresse): self
    {
        $this->complementAdresse = $complementAdresse;
        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): self
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): self
    {
        $this->ville = $ville;
        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(string $pays): self
    {
        $this->pays = $pays;
        return $this;
    }

    public function getType(): ?bool
    {
        return $this->type;
    }

    public function setType(bool $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getSiege(): ?Siege
    {
        return $this->siege;
    }

    public function setSiege(?Siege $siege): self
    {
        $this->siege = $siege;
        return $this;
    }

    public function getDashbord(): ?Dashbord
    {
        return $this->dashbord;
    }

    public function setDashbord(?Dashbord $dashbord): self
    {
        $this->dashbord = $dashbord;
        return $this;
    }
}