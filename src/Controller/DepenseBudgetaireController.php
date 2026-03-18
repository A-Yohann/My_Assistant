<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\DepenseBudgetaire;
use App\Service\EntrepriseActiveService;

class DepenseBudgetaireController extends AbstractController
{
    public const CATEGORIES = [
        'Fournitures'  => '#6366f1',
        'Matériel'     => '#f59e0b',
        'Transport'    => '#10b981',
        'Logiciels'    => '#3b82f6',
        'Marketing'    => '#ec4899',
        'Alimentation' => '#f97316',
        'Autre'        => '#8b5cf6',
    ];

    #[Route('/depense', name: 'depense_index')]
    public function index(EntityManagerInterface $em, Request $request, EntrepriseActiveService $entrepriseService): Response
    {
        $user = $this->getUser();
        $entrepriseActive = $entrepriseService->getEntrepriseActive();
        $depenses = [];
        $dataParCategorie = [];

        if ($entrepriseActive) {
            $depenses = $em->getRepository(DepenseBudgetaire::class)
                ->createQueryBuilder('d')
                ->where('d.user = :user')
                ->andWhere('d.entreprise = :entreprise')
                ->setParameter('user', $user)
                ->setParameter('entreprise', $entrepriseActive)
                ->orderBy('d.dateDepense', 'DESC')
                ->getQuery()
                ->getResult();

            // ✅ Calcul des totaux par catégorie pour le diagramme
            $totaux = $em->getRepository(DepenseBudgetaire::class)
                ->createQueryBuilder('d')
                ->select('d.categorie, SUM(d.montant * d.quantite) as total')
                ->where('d.user = :user')
                ->andWhere('d.entreprise = :entreprise')
                ->setParameter('user', $user)
                ->setParameter('entreprise', $entrepriseActive)
                ->groupBy('d.categorie')
                ->getQuery()
                ->getResult();

            foreach ($totaux as $row) {
                $cat = $row['categorie'];
                $dataParCategorie[] = [
                    'categorie' => $cat,
                    'total'     => round($row['total'], 2),
                    'couleur'   => self::CATEGORIES[$cat] ?? '#8b5cf6',
                ];
            }
        }

        // ✅ Formulaire d'ajout
        $depense = new DepenseBudgetaire();
        $form = $this->createForm(\App\Form\DepenseBudgetaireType::class, $depense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $depense->setUser($user);
            // ✅ Lier à l'entreprise active
            $depense->setEntreprise($entrepriseActive);
            $em->persist($depense);
            $em->flush();
            $this->addFlash('success', 'Dépense ajoutée avec succès !');
            return $this->redirectToRoute('depense_index');
        }

        return $this->render('depense/index.html.twig', [
            'depenses'         => $depenses,
            'dataParCategorie' => $dataParCategorie,
            'categories'       => self::CATEGORIES,
            'form'             => $form->createView(),
        ]);
    }

    #[Route('/depense/{id}/supprimer', name: 'depense_supprimer', requirements: ['id' => '\\d+'])]
    public function supprimer(EntityManagerInterface $em, int $id): Response
    {
        $depense = $em->getRepository(DepenseBudgetaire::class)->find($id);
        if ($depense && $depense->getUser() === $this->getUser()) {
            $em->remove($depense);
            $em->flush();
            $this->addFlash('success', 'Dépense supprimée.');
        }
        return $this->redirectToRoute('depense_index');
    }
}