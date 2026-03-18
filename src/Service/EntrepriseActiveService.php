<?php
namespace App\Service;

use App\Entity\Entreprise;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;

class EntrepriseActiveService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private Security $security
    ) {}

    // ✅ Récupère l'entreprise active depuis la session
    public function getEntrepriseActive(): ?Entreprise
    {
        $user = $this->security->getUser();
        if (!$user) return null;

        $session = $this->requestStack->getSession();
        $entrepriseId = $session->get('entreprise_active_id');

        if ($entrepriseId) {
            $entreprise = $this->em->getRepository(Entreprise::class)->find($entrepriseId);
            if ($entreprise && $entreprise->getUser() === $user) {
                return $entreprise;
            }
        }

        // ✅ Si pas de session → prendre la première entreprise
        $entreprises = $this->em->getRepository(Entreprise::class)->findBy(['user' => $user]);
        if (count($entreprises) > 0) {
            $this->setEntrepriseActive($entreprises[0]);
            return $entreprises[0];
        }

        return null;
    }

    // ✅ Définit l'entreprise active en session
    public function setEntrepriseActive(Entreprise $entreprise): void
    {
        $session = $this->requestStack->getSession();
        $session->set('entreprise_active_id', $entreprise->getId());
    }

    // ✅ Récupère toutes les entreprises de l'utilisateur
    public function getEntreprises(): array
    {
        $user = $this->security->getUser();
        if (!$user) return [];
        return $this->em->getRepository(Entreprise::class)->findBy(['user' => $user]);
    }
}