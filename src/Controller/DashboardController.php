<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Entreprise;
use Doctrine\ORM\EntityManagerInterface;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $entreprise = $em->getRepository(Entreprise::class)->findOneBy(['user' => $user]);
        return $this->render('dashboard/dashboard.html.twig', [
            'user' => $user,
            'entreprise' => $entreprise,
        ]);
    }
}
