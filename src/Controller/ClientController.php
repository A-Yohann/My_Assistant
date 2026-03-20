<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Client;
use App\Entity\Devis;
use App\Service\EntrepriseActiveService;

class ClientController extends AbstractController
{
    #[Route('/clients', name: 'client_index')]
    public function index(EntityManagerInterface $em, EntrepriseActiveService $entrepriseService): Response
    {
        $entrepriseActive = $entrepriseService->getEntrepriseActive();
        $clients = [];

        if ($entrepriseActive) {
            $clients = $em->getRepository(Client::class)
                ->createQueryBuilder('c')
                ->join('c.devis', 'd')
                ->where('d.entreprise = :entreprise')
                ->setParameter('entreprise', $entrepriseActive)
                ->orderBy('c.dateCreation', 'DESC')
                ->distinct()
                ->getQuery()
                ->getResult();
        }

        return $this->render('client/index.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/clients/{id}', name: 'client_show', requirements: ['id' => '\\d+'])]
    public function show(EntityManagerInterface $em, int $id, EntrepriseActiveService $entrepriseService): Response
    {
        $client = $em->getRepository(Client::class)->find($id);
        if (!$client || $client->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        $entrepriseActive = $entrepriseService->getEntrepriseActive();

        $devis = $em->getRepository(Devis::class)
            ->createQueryBuilder('d')
            ->where('d.client = :client')
            ->andWhere('d.entreprise = :entreprise')
            ->setParameter('client', $client)
            ->setParameter('entreprise', $entrepriseActive)
            ->orderBy('d.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('client/show.html.twig', [
            'client' => $client,
            'devis'  => $devis,
        ]);
    }

        #[Route('/client/{id}/json', name: 'client_json', requirements: ['id' => '\\d+'])]
        public function clientJson(EntityManagerInterface $em, int $id): JsonResponse
        {
            $client = $em->getRepository(Client::class)->find($id);
            if (!$client) {
                return $this->json(['error' => 'Client non trouvé'], 404);
            }

            return $this->json([
                'nom'        => $client->getNom(),
                'prenom'     => $client->getPrenom(),
                'email'      => $client->getEmail(),
                'telephone'  => $client->getTelephone(),
                'numeroRue'  => $client->getNumeroRue(),
                'nomRue'     => $client->getNomRue(),
                'codePostal' => $client->getCodePostal(),
                'ville'      => $client->getVille(),
                'pays'       => $client->getPays(),
            ]);
        }
}