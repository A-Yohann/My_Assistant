<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Dompdf\Dompdf;


class DevisController extends AbstractController {
            #[Route('/devis/{id}/envoyer', name: 'devis_envoyer', requirements: ['id' => '\\d+'])]
            public function envoyer(EntityManagerInterface $em, MailerInterface $mailer, int $id): Response
            {
                $devis = $em->getRepository(\App\Entity\Devis::class)->find($id);
                if (!$devis) {
                    throw $this->createNotFoundException('Devis non trouvé');
                }
                $entreprise = $devis->getEntreprise();
                $clientEmail = $entreprise ? $entreprise->getEmail() : 'client@email.fr';
                $clientName = $entreprise ? $entreprise->getNomEntreprise() : 'Nom du client';
                $html = $this->renderView('devis/pdf.html.twig', [
                    'client' => $clientName,
                    'date' => $devis->getDateEmission() ? $devis->getDateEmission()->format('d/m/Y') : '',
                    'numero' => $devis->getNumeroDevis(),
                    'articles' => [
                        ['libelle' => $devis->getDescription(), 'qty' => 1, 'price' => $devis->getMontantHT()],
                    ],
                    'totalHT' => $devis->getMontantHT(),
                    'tva' => $devis->getMontantHT() * $devis->getTauxTVA(),
                    'totalTTC' => $devis->getMontantTtc(),
                    'conditions' => 'Paiement sous 30 jours.',
                    'entreprise_nom' => $entreprise ? $entreprise->getNomEntreprise() : '',
                    'entreprise_tel' => $entreprise ? $entreprise->getTelephone() : '',
                    'entreprise_email' => $entreprise ? $entreprise->getEmail() : '',
                    'entreprise_adresse' => $entreprise ? ($entreprise->getNumeroRue() . ' ' . $entreprise->getNomRue() . ', ' . ($entreprise->getComplementAdresse() ? $entreprise->getComplementAdresse() . ', ' : '') . $entreprise->getCodePostal() . ' ' . $entreprise->getVille() . ', ' . $entreprise->getPays()) : '',
                ]);
                $options = new \Dompdf\Options();
                $options->set('defaultFont', 'Arial');
                $dompdf = new \Dompdf\Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $pdfOutput = $dompdf->output();
                $signUrl = $this->generateUrl('devis_signer', ['id' => $devis->getId()], true);
                $email = (new \Symfony\Component\Mime\Email())
                    ->from('no-reply@monassistant.fr')
                    ->to($clientEmail)
                    ->subject('Votre devis')
                    ->html('<p>Veuillez trouver votre devis en pièce jointe.<br><a href="' . $signUrl . '">Signer électroniquement le devis</a></p>')
                    ->attach($pdfOutput, 'devis.pdf', 'application/pdf');
                $mailer->send($email);
                $this->addFlash('success', 'Devis envoyé par mail à ' . $clientEmail);
                return $this->redirectToRoute('devis_show', ['id' => $id]);
            }
        #[Route('/devis/{id}/signer', name: 'devis_signer', requirements: ['id' => '\\d+'])]
        public function signer(EntityManagerInterface $em, int $id): Response
        {
            $devis = $em->getRepository(\App\Entity\Devis::class)->find($id);
            if (!$devis) {
                throw $this->createNotFoundException('Devis non trouvé');
            }
            $devis->setSignature(true);
            $devis->setEtat('valide');
            $em->flush();
            $this->addFlash('success', 'Devis signé avec succès !');
            return $this->redirectToRoute('devis_show', ['id' => $id]);
        }
    #[Route('/devis/{id}/pdf', name: 'devis_pdf')]
    public function pdf(EntityManagerInterface $em, int $id): Response
    {
        // Chemin absolu image test (assets/img/logo.png)
        $devis = $em->getRepository(\App\Entity\Devis::class)->find($id);
        if (!$devis) {
            throw $this->createNotFoundException('Devis non trouvé');
        }
        // Préparation des données pour le template PDF
        $entreprise = $devis->getEntreprise();
        $client = $entreprise ? $entreprise->getNomEntreprise() : 'Client';
        $articles = [
            ['libelle' => $devis->getDescription(), 'qty' => 1, 'price' => $devis->getMontantHT()],
        ];
        $adresse = $entreprise ? (
            $entreprise->getNumeroRue() . ' ' . $entreprise->getNomRue() . ', ' .
            ($entreprise->getComplementAdresse() ? $entreprise->getComplementAdresse() . ', ' : '') .
            $entreprise->getCodePostal() . ' ' . $entreprise->getVille() . ', ' . $entreprise->getPays()
        ) : '';
        $html = $this->renderView('devis/pdf.html.twig', [
            'client' => $client,
            'date' => $devis->getDateEmission() ? $devis->getDateEmission()->format('d/m/Y') : '',
            'numero' => $devis->getNumeroDevis(),
            'articles' => $articles,
            'totalHT' => $devis->getMontantHT(),
            'tva' => $devis->getMontantHT() * $devis->getTauxTVA(),
            'totalTTC' => $devis->getMontantTtc(),
            'conditions' => 'Paiement sous 30 jours.',
            'entreprise_nom' => $entreprise ? $entreprise->getNomEntreprise() : '',
            'entreprise_tel' => $entreprise ? $entreprise->getTelephone() : '',
            'entreprise_email' => $entreprise ? $entreprise->getEmail() : '',
            'entreprise_adresse' => $adresse,
        ]);
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true); // Autorise les images locales
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="devis-' . $devis->getNumeroDevis() . '.pdf"'
            ]
        );
    }

    #[Route('/devis/{id}', name: 'devis_show', requirements: ['id' => '\\d+'])]
    public function show(EntityManagerInterface $em, int $id): Response
    
    {
        $devis = $em->getRepository(\App\Entity\Devis::class)->find($id);
        if (!$devis) {
            throw $this->createNotFoundException('Devis non trouvé');
        }
        return $this->render('devis/show.html.twig', [
            'devis' => $devis
        ]);
    }
    #[Route('/devis/generer', name: 'devis_generer')]
    public function generer(EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        $devis = new \App\Entity\Devis();
        $form = $this->createForm(\App\Form\DevisType::class, $devis, [
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Lier l'entreprise sélectionnée
            /** @var \App\Entity\Entreprise|null $entreprise */
            $entreprise = $form->get('entreprise')->getData();
            if ($entreprise) {
                $devis->setEntreprise($entreprise);
                $devis->setTauxTVA($entreprise->getTva());
                $devis->setMontantTtc($devis->getMontantHT() * (1 + $entreprise->getTva()));
            }
            // Statut initial
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
    // ...existing code...
    // ...existing code...
    // ...existing code...
        #[Route('/devis', name: 'devis_index')]
    public function index(EntityManagerInterface $em): Response
    {
        // Récupérer l'utilisateur connecté
        /** @var User|null $user */
        $user = $this->getUser();
        $devisList = [];
        if ($user) {
            $devisList = $em->getRepository(\App\Entity\Devis::class)
                ->createQueryBuilder('d')
                ->join('d.entreprise', 'e')
                ->where('e.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
        }
        // Affiche la liste filtrée ou vide
        return $this->render('devis/index.html.twig', [
            'devisList' => $devisList
        ]);
    }
}
