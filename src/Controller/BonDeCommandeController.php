<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BonDeCommande;
use App\Entity\Facture;
use App\Service\EntrepriseActiveService;
use Dompdf\Dompdf;
use Dompdf\Options;

class BonDeCommandeController extends AbstractController
{
    #[Route('/bon-de-commande', name: 'bon_de_commande_index')]
    public function index(EntityManagerInterface $em, EntrepriseActiveService $entrepriseService): Response
    {
        $entrepriseActive = $entrepriseService->getEntrepriseActive();
        $bons = [];

        if ($entrepriseActive) {
            $bons = $em->getRepository(BonDeCommande::class)
                ->createQueryBuilder('b')
                ->where('b.entreprise = :entreprise')
                ->setParameter('entreprise', $entrepriseActive)
                ->orderBy('b.dateCreation', 'DESC')
                ->getQuery()
                ->getResult();
        }

        return $this->render('bon_de_commande/index.html.twig', [
            'bons' => $bons,
        ]);
    }

    #[Route('/bon-de-commande/{id}', name: 'bon_de_commande_show', requirements: ['id' => '\\d+'])]
    public function show(EntityManagerInterface $em, int $id): Response
    {
        $bon = $em->getRepository(BonDeCommande::class)->find($id);
        if (!$bon) {
            throw $this->createNotFoundException('Bon de commande non trouvé');
        }
        return $this->render('bon_de_commande/show.html.twig', [
            'bon' => $bon,
        ]);
    }

    #[Route('/bon-de-commande/{id}/pdf', name: 'bon_de_commande_pdf', requirements: ['id' => '\\d+'])]
    public function pdf(EntityManagerInterface $em, int $id): Response
    {
        $bon = $em->getRepository(BonDeCommande::class)->find($id);
        if (!$bon) {
            throw $this->createNotFoundException('Bon de commande non trouvé');
        }

        $entreprise = $bon->getEntreprise();
        $devis      = $bon->getDevis();
        $client     = $devis ? $devis->getClient() : null;

        $adresse = $entreprise ? (
            $entreprise->getNumeroRue() . ' ' . $entreprise->getNomRue() . ', ' .
            ($entreprise->getComplementAdresse() ? $entreprise->getComplementAdresse() . ', ' : '') .
            $entreprise->getCodePostal() . ' ' . $entreprise->getVille() . ', ' . $entreprise->getPays()
        ) : '';

        $clientAdresse = $client ? (
            $client->getNumeroRue() . ' ' . $client->getNomRue() . ', ' .
            $client->getCodePostal() . ' ' . $client->getVille() . ', ' . $client->getPays()
        ) : '';

        $html = $this->renderView('bon_de_commande/pdf.html.twig', [
            'numero'                  => $bon->getNumeroBon(),
            'date'                    => $bon->getDateCreation()->format('d/m/Y'),
            'articles'                => [
                ['libelle' => $bon->getDescription(), 'qty' => 1, 'price' => $bon->getMontantHT()],
            ],
            'totalHT'                 => $bon->getMontantHT(),
            'tva'                     => $bon->getMontantHT() * $bon->getTauxTVA(),
            'totalTTC'                => $bon->getMontantTtc(),
            'entreprise_nom'          => $entreprise ? $entreprise->getNomEntreprise() : '',
            'entreprise_tel'          => $entreprise ? $entreprise->getTelephone() : '',
            'entreprise_email'        => $entreprise ? $entreprise->getEmail() : '',
            'entreprise_adresse'      => $adresse,
            'client_nom'              => $client ? $client->getNom() : '',
            'client_prenom'           => $client ? $client->getPrenom() : '',
            'client_email'            => $client ? $client->getEmail() : '',
            'client_telephone'        => $client ? $client->getTelephone() : '',
            'client_adresse'          => $clientAdresse,
            // ✅ Signatures héritées du devis
            'signature_emetteur'      => $devis ? $devis->getSignatureEmetteur() : null,
            'signature_emetteur_date' => $devis && $devis->getSignatureEmetteurDate() ? $devis->getSignatureEmetteurDate()->format('d/m/Y à H:i') : null,
            'signature_client'        => $devis ? $devis->getSignatureImage() : null,
            'signature_client_date'   => $devis && $devis->getSignatureDate() ? $devis->getSignatureDate()->format('d/m/Y à H:i') : null,
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
                'Content-Disposition' => 'attachment; filename="bon-commande-' . $bon->getNumeroBon() . '.pdf"',
            ]
        );
    }

    #[Route('/bon-de-commande/{id}/payer', name: 'bon_de_commande_payer', requirements: ['id' => '\\d+'])]
    public function payer(EntityManagerInterface $em, int $id): Response
    {
        $bon = $em->getRepository(BonDeCommande::class)->find($id);
        if (!$bon) {
            throw $this->createNotFoundException('Bon de commande non trouvé');
        }

        if ($bon->getEtat() === 'paye') {
            $this->addFlash('info', 'Ce bon de commande est déjà payé.');
            return $this->redirectToRoute('bon_de_commande_show', ['id' => $id]);
        }

        $bon->setEtat('paye');

        $facture = new Facture();
        $facture->setNumeroFacture('FAC-' . date('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT));
        $facture->setDateCreation(new \DateTime());
        $facture->setDateEcheance(new \DateTime('+30 days'));
        $facture->setMontantHT($bon->getMontantHT());
        $facture->setMontantTtc($bon->getMontantTtc());
        $facture->setTauxTVA($bon->getTauxTVA());
        $facture->setDescription($bon->getDescription());
        $facture->setEntreprise($bon->getEntreprise());
        $facture->setBonDeCommande($bon);
        $facture->setEtat('impayee');

        $em->persist($facture);
        $em->flush();

        $this->addFlash('success', 'Bon de commande marqué comme payé. Facture générée automatiquement !');
        return $this->redirectToRoute('facture_show', ['id' => $facture->getId()]);
    }
}