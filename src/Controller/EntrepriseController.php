<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Entreprise;
use App\Service\EntrepriseActiveService;

class EntrepriseController extends AbstractController
{
    #[Route('/entreprise/switcher/{id}', name: 'entreprise_switcher', requirements: ['id' => '\\d+'])]
    public function switcher(int $id, EntityManagerInterface $em, EntrepriseActiveService $entrepriseService): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $entreprise = $em->getRepository(Entreprise::class)->find($id);

        if ($entreprise && $entreprise->getUser() === $user) {
            $entrepriseService->setEntrepriseActive($entreprise);
            $this->addFlash('success', 'Entreprise active : ' . $entreprise->getNomEntreprise());
        }

        // ✅ Retour à la page précédente
        $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
        return $this->redirect($referer);
    }
}