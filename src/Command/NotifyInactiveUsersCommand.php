<?php
namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:Notify-Inactive-Users',
    description: 'Envoie un mail aux utilisateurs inactifs depuis 2 ans'
)]
class NotifyInactiveUsersCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $twoYearsAgo = (new \DateTime())->modify('-2 years');

        $users = $this->userRepository->findInactiveSince($twoYearsAgo);

        if (empty($users)) {
            $output->writeln('✅ Aucun utilisateur inactif depuis 2 ans.');
            return Command::SUCCESS;
        }

        foreach ($users as $user) {
            if (!$user->getEmail()) {
                $output->writeln('⚠ Utilisateur sans email : ID ' . $user->getId());
                continue;
            }

            $from = $_ENV['MAILER_FROM'] ?? 'no-reply@my-assistant.fr';

            $email = (new Email())
                ->from($from)
                ->to($user->getEmail())
                ->subject('Votre compte sera supprimé pour inactivité')
                ->html('
                    <p>Bonjour ' . htmlspecialchars($user->getPrenom() ?? $user->getEmail()) . ',</p>
                    <p>Nous avons remarqué que vous n\'avez pas utilisé votre compte depuis plus de 2 ans.</p>
                    <p>Si vous ne vous reconnectez pas bientôt, votre compte sera supprimé pour inactivité.</p>
                    <p>Cordialement,<br>L\'équipe de tonassistant.fr</p>
                ');

            try {
                $this->mailer->send($email);
                $output->writeln('✅ Mail envoyé à ' . $user->getEmail());
            } catch (\Exception $e) {
                $output->writeln('❌ Erreur pour ' . $user->getEmail() . ' : ' . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}