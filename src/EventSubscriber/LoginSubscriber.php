<?php
namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RouterInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    // ✅ Connexion réussie → reset les tentatives
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if ($user instanceof User) {
            $user->setLoginAttempts(0);
            $user->setLockedUntil(null);
            $this->em->flush();
        }
    }

    // ✅ Échec de connexion → incrémente les tentatives
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request  = $event->getRequest();
        $email    = $request->request->get('_username');

        if (!$email) return;

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) return;

        // ✅ Si déjà bloqué on ne fait rien
        if ($user->isLocked()) return;

        $attempts = $user->getLoginAttempts() + 1;
        $user->setLoginAttempts($attempts);

        // ✅ Après 5 tentatives → compte bloqué
        if ($attempts >= 5) {
            $user->setLockedUntil(new \DateTime('+1 hour')); // bloqué pendant 1 heure
        }

        $this->em->flush();
    }
}