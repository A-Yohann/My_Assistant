<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BonDeCommande;
use App\Entity\Facture;
use App\Service\EntrepriseActiveService;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class BonDeCommandeController extends AbstractController
{
    #[Route('/bon-de-commande', name: 'bon_de_commande_index')]
    public function index(EntityManagerInterface $em, EntrepriseActiveService $entrepriseService, Request $request, PaginatorInterface $paginator): Response
    {
        $entrepriseActive = $entrepriseService->getEntrepriseActive();

        $qb = $em->getRepository(BonDeCommande::class)
            ->createQueryBuilder('b')
            ->where('b.entreprise = :entreprise')
            ->setParameter('entreprise', $entrepriseActive ?? 0)
            ->orderBy('b.dateCreation', 'DESC');

        $bons = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

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

    private function buildPdfVariables(BonDeCommande $bon): array
    {
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

        return [
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
            'entreprise_siret'        => $entreprise ? $entreprise->getSiret() : '', // ✅ SIRET
            'client_nom'              => $client ? $client->getNom() : '',
            'client_prenom'           => $client ? $client->getPrenom() : '',
            'client_email'            => $client ? $client->getEmail() : '',
            'client_telephone'        => $client ? $client->getTelephone() : '',
            'client_adresse'          => $clientAdresse,
            'signature_emetteur'      => $devis ? $devis->getSignatureEmetteur() : null,
            'signature_emetteur_date' => $devis && $devis->getSignatureEmetteurDate() ? $devis->getSignatureEmetteurDate()->format('d/m/Y à H:i') : null,
            'signature_client'        => $devis ? $devis->getSignatureImage() : null,
            'signature_client_date'   => $devis && $devis->getSignatureDate() ? $devis->getSignatureDate()->format('d/m/Y à H:i') : null,
        ];
    }

    private function generatePdf(array $variables, string $template): string
    {
        $html = $this->renderView($template, $variables);
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    #[Route('/bon-de-commande/{id}/pdf', name: 'bon_de_commande_pdf', requirements: ['id' => '\\d+'])]
    public function pdf(EntityManagerInterface $em, int $id): Response
    {
        $bon = $em->getRepository(BonDeCommande::class)->find($id);
        if (!$bon) {
            throw $this->createNotFoundException('Bon de commande non trouvé');
        }

        $pdfOutput = $this->generatePdf($this->buildPdfVariables($bon), 'bon_de_commande/pdf.html.twig');

        return new Response($pdfOutput, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bon-commande-' . $bon->getNumeroBon() . '.pdf"',
        ]);
    }

    // ✅ Envoi du bon de commande par mail avec lien Stripe
    #[Route('/bon-de-commande/{id}/envoyer', name: 'bon_de_commande_envoyer', requirements: ['id' => '\\d+'])]
    public function envoyer(EntityManagerInterface $em, MailerInterface $mailer, int $id): Response
    {
        $bon = $em->getRepository(BonDeCommande::class)->find($id);
        if (!$bon) {
            throw $this->createNotFoundException('Bon de commande non trouvé');
        }

        $devis  = $bon->getDevis();
        $client = $devis ? $devis->getClient() : null;
        $clientEmail = $client ? $client->getEmail() : null;
        $clientName  = $client ? $client->getNom() . ' ' . $client->getPrenom() : 'Client';

        if (!$clientEmail) {
            $this->addFlash('error', 'Aucun email client associé à ce bon de commande.');
            return $this->redirectToRoute('bon_de_commande_show', ['id' => $id]);
        }

        $payUrl = $this->generateUrl('bon_de_commande_stripe', [
            'id' => $id
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $from = $_ENV['MAILER_FROM'] ?? 'no-reply@my-assistant.fr';

        $email = (new Email())
            ->from($from)
            ->to($clientEmail)
            ->subject('Votre bon de commande ' . $bon->getNumeroBon())
            ->html('
                <p>Bonjour ' . htmlspecialchars($clientName) . ',</p>
                <p>Votre bon de commande <strong>' . $bon->getNumeroBon() . '</strong>
                d\'un montant de <strong>' . number_format($bon->getMontantTtc(), 2, ',', ' ') . ' €</strong> est prêt.</p>
                <p>Cliquez sur le bouton ci-dessous pour procéder au paiement en ligne :</p>
                <p>
                    <a href="' . $payUrl . '" style="background:#3B0764;color:white;padding:12px 24px;border-radius:5px;text-decoration:none;font-weight:bold;">
                        💳 Payer en ligne
                    </a>
                </p>
                <p>Cordialement,<br>L\'équipe My Assistant</p>
            ');

        try {
            $mailer->send($email);
            $this->addFlash('success', 'Bon de commande envoyé par mail à ' . $clientEmail);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('bon_de_commande_show', ['id' => $id]);
    }

    // ✅ Page Stripe Checkout
    #[Route('/bon-de-commande/{id}/stripe', name: 'bon_de_commande_stripe', requirements: ['id' => '\\d+'])]
    public function stripe(EntityManagerInterface $em, int $id): Response
    {
        $bon = $em->getRepository(BonDeCommande::class)->find($id);
        if (!$bon) {
            throw $this->createNotFoundException('Bon de commande non trouvé');
        }

        if ($bon->getEtat() === 'paye') {
            $this->addFlash('info', 'Ce bon de commande est déjà payé.');
            return $this->redirectToRoute('bon_de_commande_show', ['id' => $id]);
        }

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => (int)($bon->getMontantTtc() * 100),
                    'product_data' => [
                        'name' => 'Bon de commande ' . $bon->getNumeroBon(),
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => $this->generateUrl('bon_de_commande_stripe_success', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url'  => $this->generateUrl('bon_de_commande_show', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url, 303);
    }

    // ✅ Succès paiement Stripe → facture générée auto
    #[Route('/bon-de-commande/{id}/stripe-success', name: 'bon_de_commande_stripe_success', requirements: ['id' => '\\d+'])]
    public function stripeSuccess(EntityManagerInterface $em, int $id): Response
    {
        $bon = $em->getRepository(BonDeCommande::class)->find($id);
        if (!$bon) {
            throw $this->createNotFoundException('Bon de commande non trouvé');
        }

        if ($bon->getEtat() !== 'paye') {
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
            $facture->setEtat('payee'); // ✅ Payée via Stripe

            $em->persist($facture);
            $em->flush();
        }

        $this->addFlash('success', 'Paiement effectué ! Facture générée automatiquement.');
        return $this->redirectToRoute('facture_show', ['id' => $bon->getId()]);
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