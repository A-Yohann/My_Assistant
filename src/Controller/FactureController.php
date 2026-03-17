<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Facture;
use Dompdf\Dompdf;
use Dompdf\Options;

class FactureController extends AbstractController
{
    #[Route('/facture', name: 'facture_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $factures = [];
        if ($user) {
            $factures = $em->getRepository(Facture::class)
                ->createQueryBuilder('f')
                ->join('f.entreprise', 'e')
                ->where('e.user = :user')
                ->setParameter('user', $user)
                ->orderBy('f.dateCreation', 'DESC')
                ->getQuery()
                ->getResult();
        }
        return $this->render('facture/index.html.twig', [
            'factures' => $factures,
        ]);
    }

    #[Route('/facture/{id}', name: 'facture_show', requirements: ['id' => '\\d+'])]
    public function show(EntityManagerInterface $em, int $id): Response
    {
        $facture = $em->getRepository(Facture::class)->find($id);
        if (!$facture) {
            throw $this->createNotFoundException('Facture non trouvée');
        }
        return $this->render('facture/show.html.twig', [
            'facture' => $facture,
        ]);
    }

    #[Route('/facture/{id}/pdf', name: 'facture_pdf', requirements: ['id' => '\\d+'])]
    public function pdf(EntityManagerInterface $em, int $id): Response
    {
        $facture = $em->getRepository(Facture::class)->find($id);
        if (!$facture) {
            throw $this->createNotFoundException('Facture non trouvée');
        }

        $entreprise = $facture->getEntreprise();
        $client = $facture->getBonDeCommande() ? $facture->getBonDeCommande()->getDevis()->getClient() : null;

        $adresse = $entreprise ? (
            $entreprise->getNumeroRue() . ' ' . $entreprise->getNomRue() . ', ' .
            ($entreprise->getComplementAdresse() ? $entreprise->getComplementAdresse() . ', ' : '') .
            $entreprise->getCodePostal() . ' ' . $entreprise->getVille() . ', ' . $entreprise->getPays()
        ) : '';

        $clientAdresse = $client ? (
            $client->getNumeroRue() . ' ' . $client->getNomRue() . ', ' .
            $client->getCodePostal() . ' ' . $client->getVille() . ', ' . $client->getPays()
        ) : '';

        $html = $this->renderView('facture/pdf.html.twig', [
            'numero'             => $facture->getNumeroFacture(),
            'date'               => $facture->getDateCreation()->format('d/m/Y'),
            'dateEcheance'       => $facture->getDateEcheance() ? $facture->getDateEcheance()->format('d/m/Y') : '',
            'articles'           => [
                ['libelle' => $facture->getDescription(), 'qty' => 1, 'price' => $facture->getMontantHT()],
            ],
            'totalHT'            => $facture->getMontantHT(),
            'tva'                => $facture->getMontantHT() * $facture->getTauxTVA(),
            'totalTTC'           => $facture->getMontantTtc(),
            'entreprise_nom'     => $entreprise ? $entreprise->getNomEntreprise() : '',
            'entreprise_tel'     => $entreprise ? $entreprise->getTelephone() : '',
            'entreprise_email'   => $entreprise ? $entreprise->getEmail() : '',
            'entreprise_adresse' => $adresse,
            'client_nom'         => $client ? $client->getNom() : '',
            'client_prenom'      => $client ? $client->getPrenom() : '',
            'client_email'       => $client ? $client->getEmail() : '',
            'client_telephone'   => $client ? $client->getTelephone() : '',
            'client_adresse'     => $clientAdresse,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="facture-' . $facture->getNumeroFacture() . '.pdf"',
            ]
        );
    }

    #[Route('/facture/{id}/payer', name: 'facture_payer', requirements: ['id' => '\\d+'])]
    public function payer(EntityManagerInterface $em, int $id): Response
    {
        $facture = $em->getRepository(Facture::class)->find($id);
        if (!$facture) {
            throw $this->createNotFoundException('Facture non trouvée');
        }

        if ($facture->getEtat() === 'payee') {
            $this->addFlash('info', 'Cette facture est déjà payée.');
            return $this->redirectToRoute('facture_show', ['id' => $id]);
        }

        $facture->setEtat('payee');
        $em->flush();

        $this->addFlash('success', 'Facture marquée comme payée !');
        return $this->redirectToRoute('facture_show', ['id' => $id]);
    }
}