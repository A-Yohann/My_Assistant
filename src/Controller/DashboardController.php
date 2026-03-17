<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Entreprise;
use App\Entity\Note;
use App\Entity\Facture;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $entreprise = $em->getRepository(Entreprise::class)->findOneBy(['user' => $user]);
        $notes = [];
        $lastDevis = [];
        $lastDepenses = [];
        $lastClients = [];
        $devisEnAttente = 0;
        $totalFactures = 0;

        if ($user) {
            $notes = $em->getRepository(Note::class)->findBy(['user' => $user], ['dateCreation' => 'DESC'], 3);

            $lastDevis = $em->getRepository(\App\Entity\Devis::class)->createQueryBuilder('d')
                ->join('d.entreprise', 'e')
                ->where('e.user = :user')
                ->setParameter('user', $user)
                ->orderBy('d.dateCreation', 'DESC')
                ->setMaxResults(3)
                ->getQuery()
                ->getResult();

            // ✅ Comptage des devis en attente
            $devisEnAttente = $em->getRepository(\App\Entity\Devis::class)->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->join('d.entreprise', 'e')
                ->where('e.user = :user')
                ->andWhere('d.etat = :etat')
                ->setParameter('user', $user)
                ->setParameter('etat', 'en_attente')
                ->getQuery()
                ->getSingleScalarResult();

            // ✅ Comptage total des factures
            $totalFactures = $em->getRepository(Facture::class)->createQueryBuilder('f')
                ->select('COUNT(f.id)')
                ->join('f.entreprise', 'e')
                ->where('e.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getSingleScalarResult();

            // ✅ 3 dernières dépenses
            $lastDepenses = $em->getRepository(\App\Entity\DepenseBudgetaire::class)
                ->createQueryBuilder('d')
                ->where('d.user = :user')
                ->setParameter('user', $user)
                ->orderBy('d.dateDepense', 'DESC')
                ->setMaxResults(3)
                ->getQuery()
                ->getResult();

            // ✅ 3 derniers clients
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
            'user'           => $user,
            'entreprise'     => $entreprise,
            'lastNotes'      => $notes,
            'lastDevis'      => $lastDevis,
            'lastDepenses'   => $lastDepenses,
            'lastClients'    => $lastClients,
            'devisEnAttente' => $devisEnAttente,
            'totalFactures'  => $totalFactures,
        ]);
    }
}