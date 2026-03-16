<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Entreprise;
use App\Entity\Note;
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
        }
        return $this->render('dashboard/dashboard.html.twig', [
            'user' => $user,
            'entreprise' => $entreprise,
            'lastNotes' => $notes,
            'lastDevis' => $lastDevis,
        ]);
    }
}
