<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Client;

class ClientController extends AbstractController
{
    #[Route('/clients', name: 'client_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $clients = [];
        if ($user) {
            $clients = $em->getRepository(Client::class)
                ->createQueryBuilder('c')
                ->where('c.user = :user')
                ->setParameter('user', $user)
                ->orderBy('c.dateCreation', 'DESC')
                ->getQuery()
                ->getResult();
        }
        return $this->render('client/index.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/clients/{id}', name: 'client_show', requirements: ['id' => '\\d+'])]
    public function show(EntityManagerInterface $em, int $id): Response
    {
        $client = $em->getRepository(Client::class)->find($id);
        if (!$client || $client->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        // ✅ Récupérer les devis du client
        $devis = $em->getRepository(\App\Entity\Devis::class)
            ->createQueryBuilder('d')
            ->where('d.client = :client')
            ->setParameter('client', $client)
            ->orderBy('d.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('client/show.html.twig', [
            'client' => $client,
            'devis'  => $devis,
        ]);
    }
}