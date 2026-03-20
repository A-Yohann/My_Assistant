<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Devis;
use App\Form\DevisType;
use App\Service\EntrepriseActiveService;
use Dompdf\Dompdf;
use Dompdf\Options;

class DevisController extends AbstractController
{
    #[Route('/devis', name: 'devis_index')]
    public function index(EntityManagerInterface $em, EntrepriseActiveService $entrepriseService): Response
    {
        $entrepriseActive = $entrepriseService->getEntrepriseActive();
        $devisList = [];

        if ($entrepriseActive) {
            $devisList = $em->getRepository(Devis::class)
                ->createQueryBuilder('d')
                ->where('d.entreprise = :entreprise')
                ->setParameter('entreprise', $entrepriseActive)
                ->orderBy('d.dateCreation', 'DESC')
                ->getQuery()
                ->getResult();
        }

        return $this->render('devis/index.html.twig', [
            'devisList' => $devisList
        ]);
    }

    #[Route('/devis/generer', name: 'devis_generer')]
    public function generer(EntityManagerInterface $em, Request $request): Response
    {
        $devis = new Devis();

        // ✅ Numérotation automatique
        $lastDevis = $em->getRepository(Devis::class)
            ->createQueryBuilder('d')
            ->orderBy('d.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $nextNumber = 1;
        if ($lastDevis) {
            $parts = explode('-', $lastDevis->getNumeroDevis());
            $nextNumber = ((int) end($parts)) + 1;
        }
        $numeroAuto = 'DEV-' . date('Y') . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        $devis->setNumeroDevis($numeroAuto);

        $form = $this->createForm(DevisType::class, $devis, [
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\Entreprise|null $entreprise */
            $entreprise = $form->get('entreprise')->getData();
            if ($entreprise) {
                $devis->setEntreprise($entreprise);
                $devis->setTauxTVA($entreprise->getTva());
                $devis->setMontantTtc($devis->getMontantHT() * (1 + $entreprise->getTva()));
            }

            // ✅ Création et enregistrement du client
            $client = new \App\Entity\Client();
            $client->setNom($form->get('clientNom')->getData());
            $client->setPrenom($form->get('clientPrenom')->getData());
            $client->setEmail($form->get('clientEmail')->getData());
            $client->setTelephone($form->get('clientTelephone')->getData());
            $client->setNumeroRue($form->get('clientNumeroRue')->getData());
            $client->setNomRue($form->get('clientNomRue')->getData());
            $client->setCodePostal($form->get('clientCodePostal')->getData());
            $client->setVille($form->get('clientVille')->getData());
            $client->setPays($form->get('clientPays')->getData());
            $client->setDateCreation(new \DateTime());
            $client->setUser($this->getUser());

            $em->persist($client);

            // ✅ Signature émetteur
            $signatureEmetteur = $request->request->get('signature_emetteur');
            if ($signatureEmetteur) {
                $devis->setSignatureEmetteur($signatureEmetteur);
                $devis->setSignatureEmetteurDate(new \DateTime());
            }

            $devis->setClient($client);
            $devis->setEtat('en_attente');

            $em->persist($devis);
            $em->flush();

            $this->addFlash('success', 'Devis enregistré avec succès !');
            return $this->redirectToRoute('devis_index');
        }

        return $this->render('devis/generer.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/devis/{id}', name: 'devis_show', requirements: ['id' => '\\d+'])]
    public function show(EntityManagerInterface $em, int $id): Response
    {
        $devis = $em->getRepository(Devis::class)->find($id);
        if (!$devis) {
            throw $this->createNotFoundException('Devis non trouvé');
        }
        return $this->render('devis/show.html.twig', [
            'devis' => $devis
        ]);
    }

    #[Route('/devis/{id}/pdf', name: 'devis_pdf', requirements: ['id' => '\\d+'])]
    public function pdf(EntityManagerInterface $em, int $id): Response
    {
        $devis = $em->getRepository(Devis::class)->find($id);
        if (!$devis) {
            throw $this->createNotFoundException('Devis non trouvé');
        }

        $entreprise = $devis->getEntreprise();
        $client = $devis->getClient();

        $adresse = $entreprise ? (
            $entreprise->getNumeroRue() . ' ' . $entreprise->getNomRue() . ', ' .
            ($entreprise->getComplementAdresse() ? $entreprise->getComplementAdresse() . ', ' : '') .
            $entreprise->getCodePostal() . ' ' . $entreprise->getVille() . ', ' . $entreprise->getPays()
        ) : '';

        $clientAdresse = $client ? (
            $client->getNumeroRue() . ' ' . $client->getNomRue() . ', ' .
            $client->getCodePostal() . ' ' . $client->getVille() . ', ' . $client->getPays()
        ) : '';

        $html = $this->renderView('devis/pdf.html.twig', [
            'numero'                  => $devis->getNumeroDevis(),
            'date'                    => $devis->getDateEmission() ? $devis->getDateEmission()->format('d/m/Y') : '',
            'articles'                => [
                ['libelle' => $devis->getDescription(), 'qty' => 1, 'price' => $devis->getMontantHT()],
            ],
            'totalHT'                 => $devis->getMontantHT(),
            'tva'                     => $devis->getMontantHT() * $devis->getTauxTVA(),
            'totalTTC'                => $devis->getMontantTtc(),
            'conditions'              => 'Paiement sous 30 jours.',
            'entreprise_nom'          => $entreprise ? $entreprise->getNomEntreprise() : '',
            'entreprise_tel'          => $entreprise ? $entreprise->getTelephone() : '',
            'entreprise_email'        => $entreprise ? $entreprise->getEmail() : '',
            'entreprise_adresse'      => $adresse,
            'client_nom'              => $client ? $client->getNom() : '',
            'client_prenom'           => $client ? $client->getPrenom() : '',
            'client_email'            => $client ? $client->getEmail() : '',
            'client_telephone'        => $client ? $client->getTelephone() : '',
            'client_adresse'          => $clientAdresse,
            'signature_emetteur'      => $devis->getSignatureEmetteur(),
            'signature_emetteur_date' => $devis->getSignatureEmetteurDate() ? $devis->getSignatureEmetteurDate()->format('d/m/Y à H:i') : null,
            'signature_client'        => $devis->getSignatureImage(),
            'signature_client_date'   => $devis->getSignatureDate() ? $devis->getSignatureDate()->format('d/m/Y à H:i') : null,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="devis-' . $devis->getNumeroDevis() . '.pdf"',
            ]
        );
    }

    #[Route('/devis/{id}/envoyer', name: 'devis_envoyer', requirements: ['id' => '\\d+'])]
    public function envoyer(EntityManagerInterface $em, MailerInterface $mailer, int $id): Response
    {
        $devis = $em->getRepository(Devis::class)->find($id);
        if (!$devis) {
            throw $this->createNotFoundException('Devis non trouvé');
        }

        $token = bin2hex(random_bytes(32));
        $devis->setSignatureToken($token);
        $em->flush();

        $entreprise = $devis->getEntreprise();
        $client = $devis->getClient();
        $clientEmail = $client ? $client->getEmail() : ($entreprise ? $entreprise->getEmail() : 'client@email.fr');
        $clientName  = $client ? $client->getNom() . ' ' . $client->getPrenom() : ($entreprise ? $entreprise->getNomEntreprise() : 'Nom du client');

        $adresse = $entreprise ? (
            $entreprise->getNumeroRue() . ' ' . $entreprise->getNomRue() . ', ' .
            ($entreprise->getComplementAdresse() ? $entreprise->getComplementAdresse() . ', ' : '') .
            $entreprise->getCodePostal() . ' ' . $entreprise->getVille() . ', ' . $entreprise->getPays()
        ) : '';

        $clientAdresse = $client ? (
            $client->getNumeroRue() . ' ' . $client->getNomRue() . ', ' .
            $client->getCodePostal() . ' ' . $client->getVille() . ', ' . $client->getPays()
        ) : '';

        $html = $this->renderView('devis/pdf.html.twig', [
            'numero'                  => $devis->getNumeroDevis(),
            'date'                    => $devis->getDateEmission() ? $devis->getDateEmission()->format('d/m/Y') : '',
            'articles'                => [
                ['libelle' => $devis->getDescription(), 'qty' => 1, 'price' => $devis->getMontantHT()],
            ],
            'totalHT'                 => $devis->getMontantHT(),
            'tva'                     => $devis->getMontantHT() * $devis->getTauxTVA(),
            'totalTTC'                => $devis->getMontantTtc(),
            'conditions'              => 'Paiement sous 30 jours.',
            'entreprise_nom'          => $entreprise ? $entreprise->getNomEntreprise() : '',
            'entreprise_tel'          => $entreprise ? $entreprise->getTelephone() : '',
            'entreprise_email'        => $entreprise ? $entreprise->getEmail() : '',
            'entreprise_adresse'      => $adresse,
            'client_nom'              => $client ? $client->getNom() : '',
            'client_prenom'           => $client ? $client->getPrenom() : '',
            'client_email'            => $client ? $client->getEmail() : '',
            'client_telephone'        => $client ? $client->getTelephone() : '',
            'client_adresse'          => $clientAdresse,
            'signature_emetteur'      => $devis->getSignatureEmetteur(),
            'signature_emetteur_date' => $devis->getSignatureEmetteurDate() ? $devis->getSignatureEmetteurDate()->format('d/m/Y à H:i') : null,
            'signature_client'        => $devis->getSignatureImage(),
            'signature_client_date'   => $devis->getSignatureDate() ? $devis->getSignatureDate()->format('d/m/Y à H:i') : null,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        $signUrl = $this->generateUrl('devis_signer', [
            'id'    => $devis->getId(),
            'token' => $token,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new \Symfony\Component\Mime\Email())
            ->from('no-reply@monassistant.fr')
            ->to($clientEmail)
            ->subject('Votre devis ' . $devis->getNumeroDevis())
            ->html('
                <p>Bonjour ' . $clientName . ',</p>
                <p>Veuillez trouver votre devis en pièce jointe.</p>
                <p>
                    <a href="' . $signUrl . '" style="background:#3B0764;color:white;padding:10px 20px;border-radius:5px;text-decoration:none;">
                        Signer électroniquement le devis
                    </a>
                </p>
                <p>Ce lien est personnel et sécurisé.</p>
            ')
            ->attach($pdfOutput, 'devis-' . $devis->getNumeroDevis() . '.pdf', 'application/pdf');

        $mailer->send($email);
        $this->addFlash('success', 'Devis envoyé par mail à ' . $clientEmail);
        return $this->redirectToRoute('devis_show', ['id' => $id]);
    }

    #[Route('/devis/{id}/signer/{token}', name: 'devis_signer', requirements: ['id' => '\\d+'])]
    public function signer(EntityManagerInterface $em, int $id, string $token): Response
    {
        $devis = $em->getRepository(Devis::class)->find($id);

        if (!$devis || $devis->getSignatureToken() !== $token) {
            throw $this->createNotFoundException('Lien de signature invalide ou expiré.');
        }

        if ($devis->isSignature()) {
            $this->addFlash('info', 'Ce devis a déjà été signé.');
            return $this->redirectToRoute('devis_show', ['id' => $id]);
        }

        return $this->render('devis/signer.html.twig', [
            'devis' => $devis,
            'token' => $token,
        ]);
    }

    #[Route('/devis/{id}/signer/{token}/confirmer', name: 'devis_signer_confirmer', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function signerConfirmer(EntityManagerInterface $em, Request $request, int $id, string $token): Response
    {
        $devis = $em->getRepository(Devis::class)->find($id);

        if (!$devis || $devis->getSignatureToken() !== $token) {
            throw $this->createNotFoundException('Lien de signature invalide ou expiré.');
        }

        if ($devis->isSignature()) {
            $this->addFlash('info', 'Ce devis a déjà été signé.');
            return $this->redirectToRoute('devis_show', ['id' => $id]);
        }

        $signatureImage = $request->request->get('signature_image');

        if (!$signatureImage) {
            $this->addFlash('error', 'Veuillez dessiner votre signature avant de valider.');
            return $this->redirectToRoute('devis_signer', ['id' => $id, 'token' => $token]);
        }

        $devis->setSignature(true);
        $devis->setEtat('valide');
        $devis->setSignatureDate(new \DateTime());
        $devis->setSignatureImage($signatureImage);
        $devis->setSignatureToken(null);

        $bon = new \App\Entity\BonDeCommande();
        $bon->setNumeroBon('BC-' . date('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT));
        $bon->setDateCreation(new \DateTime());
        $bon->setMontantHT($devis->getMontantHT());
        $bon->setMontantTtc($devis->getMontantTtc());
        $bon->setTauxTVA($devis->getTauxTVA());
        $bon->setDescription($devis->getDescription());
        $bon->setEntreprise($devis->getEntreprise());
        $bon->setDevis($devis);
        $bon->setEtat('en_attente');

        $em->persist($bon);
        $em->flush();

        return $this->render('devis/signature_confirmee.html.twig', [
            'devis' => $devis,
            'bon'   => $bon,
        ]);
    }
}