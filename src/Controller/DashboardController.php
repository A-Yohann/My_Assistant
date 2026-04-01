<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Note;
use App\Entity\Facture;
use App\Entity\Client;
use App\Service\EntrepriseActiveService;
use Doctrine\ORM\EntityManagerInterface;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(EntityManagerInterface $em, EntrepriseActiveService $entrepriseService): Response
    {
        $user = $this->getUser();
        $entrepriseActive = $entrepriseService->getEntrepriseActive();
        $entreprises = $entrepriseService->getEntreprises();
        $notes = [];
        $lastDevis = [];
        $lastDepenses = [];
        $lastClients = [];
        $devisEnAttente = 0;
        $totalFactures = 0;

        if ($user && $entrepriseActive) {
            $notes = $em->getRepository(Note::class)->findBy(['user' => $user], ['dateCreation' => 'DESC'], 3);

            $lastDevis = $em->getRepository(\App\Entity\Devis::class)->createQueryBuilder('d')
                ->where('d.entreprise = :entreprise')
                ->setParameter('entreprise', $entrepriseActive)
                ->orderBy('d.dateCreation', 'DESC')
                ->setMaxResults(3)
                ->getQuery()
                ->getResult();

            $devisEnAttente = $em->getRepository(\App\Entity\Devis::class)->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->where('d.entreprise = :entreprise')
                ->andWhere('d.etat = :etat')
                ->setParameter('entreprise', $entrepriseActive)
                ->setParameter('etat', 'en_attente')
                ->getQuery()
                ->getSingleScalarResult();

            $totalFactures = $em->getRepository(Facture::class)->createQueryBuilder('f')
                ->select('COUNT(f.id)')
                ->where('f.entreprise = :entreprise')
                ->setParameter('entreprise', $entrepriseActive)
                ->getQuery()
                ->getSingleScalarResult();

            $lastDepenses = $em->getRepository(\App\Entity\DepenseBudgetaire::class)
                ->createQueryBuilder('d')
                ->where('d.user = :user')
                ->setParameter('user', $user)
                ->orderBy('d.dateDepense', 'DESC')
                ->setMaxResults(3)
                ->getQuery()
                ->getResult();

            $lastClients = $em->getRepository(Client::class)
                ->createQueryBuilder('c')
                ->where('c.user = :user')
                ->setParameter('user', $user)
                ->orderBy('c.dateCreation', 'DESC')
                ->setMaxResults(3)
                ->getQuery()
                ->getResult();
        }

        return $this->render('dashboard/dashboard.html.twig', [
            'user'             => $user,
            'entreprise'       => $entrepriseActive,
            'entreprises'      => $entreprises,
            'lastNotes'        => $notes,
            'lastDevis'        => $lastDevis,
            'lastDepenses'     => $lastDepenses,
            'lastClients'      => $lastClients,
            'devisEnAttente'   => $devisEnAttente,
            'totalFactures'    => $totalFactures,
        ]);
    }
}