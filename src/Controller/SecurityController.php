<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, EntityManagerInterface $em): Response
    {
        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // ✅ Vérifier si le compte est bloqué
        $isLocked = false;
        $attemptsRestants = 3;

        if ($lastUsername) {
            $user = $em->getRepository(User::class)->findOneBy(['email' => $lastUsername]);
            if ($user) {
                if ($user->isLocked()) {
                    $isLocked = true;
                }
                $attemptsRestants = max(0, 3 - $user->getLoginAttempts());
            }
        }

        return $this->render('security/login.html.twig', [
            'last_username'     => $lastUsername,
            'error'             => $error,
            'is_locked'         => $isLocked,
            'attempts_restants' => $attemptsRestants,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}