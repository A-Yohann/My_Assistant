<?php
namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, string $token): Response
    {
        $resetToken = $em->getRepository(PasswordResetToken::class)->findOneBy(['token' => $token]);
        if (!$resetToken || $resetToken->getExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }
        $user = $em->getRepository(User::class)->findOneBy(['email' => $resetToken->getEmail()]);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_forgot_password');
        }
        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            if ($password) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $em->remove($resetToken);
                $em->flush();
                $this->addFlash('success', 'Votre mot de passe a été réinitialisé.');
                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash('error', 'Veuillez saisir un mot de passe.');
            }
        }
        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
        ]);
    }
}
