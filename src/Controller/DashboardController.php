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
        if ($user) {
            $notes = $em->getRepository(Note::class)->findBy(['user' => $user], ['dateCreation' => 'DESC'], 3);
        }
        return $this->render('dashboard/dashboard.html.twig', [
            'user' => $user,
            'entreprise' => $entreprise,
            'lastNotes' => $notes,
        ]);
    }
}
