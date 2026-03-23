<?php
namespace App\Command;

use App\Entity\Facture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:relance-factures',
    description: 'Envoie des relances pour les factures impayées'
)]
class RelanceFactureCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //  Factures impayées depuis plus de 15 jours
        $dateLimit = new \DateTime('-15 days');

        $factures = $this->em->getRepository(Facture::class)
            ->createQueryBuilder('f')
            ->where('f.etat = :etat')
            ->andWhere('f.dateEcheance < :dateLimit')
            ->setParameter('etat', 'impayee')
            ->setParameter('dateLimit', $dateLimit)
            ->getQuery()
            ->getResult();

        if (empty($factures)) {
            $output->writeln('Aucune facture à relancer.');
            return Command::SUCCESS;
        }

        foreach ($factures as $facture) {
            $bon    = $facture->getBonDeCommande();
            $devis  = $bon ? $bon->getDevis() : null;
            $client = $devis ? $devis->getClient() : null;

            if (!$client || !$client->getEmail()) {
                continue;
            }

            $from = $_ENV['MAILER_FROM'] ?? 'no-reply@my-assistant.fr';

            $email = (new Email())
                ->from($from)
                ->to($client->getEmail())
                ->subject('Relance — Facture ' . $facture->getNumeroFacture() . ' impayée')
                ->html('
                    <p>Bonjour ' . htmlspecialchars($client->getNom() . ' ' . $client->getPrenom()) . ',</p>
                    <p>Nous vous contactons concernant la facture <strong>' . $facture->getNumeroFacture() . '</strong> 
                    d\'un montant de <strong>' . number_format($facture->getMontantTtc(), 2, ',', ' ') . ' €</strong> 
                    qui reste impayée depuis le ' . $facture->getDateEcheance()->format('d/m/Y') . '.</p>
                    <p>Merci de procéder au règlement dans les plus brefs délais.</p>
                    <p>Cordialement,<br>L\'équipe My Assistant</p>
                ');

            try {
                $this->mailer->send($email);
                $output->writeln('✅ Relance envoyée à ' . $client->getEmail() . ' pour ' . $facture->getNumeroFacture());
            } catch (\Exception $e) {
                $output->writeln('❌ Erreur pour ' . $facture->getNumeroFacture() . ' : ' . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}