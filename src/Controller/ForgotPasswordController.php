<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgot(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $emailSent = false;
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            if ($email) {
                $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($user) {
                    // Générer un token unique
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = (new \DateTime())->modify('+1 hour');
                    // Stocker le token
                    $resetToken = new PasswordResetToken();
                    $resetToken->setEmail($email);
                    $resetToken->setToken($token);
                    $resetToken->setExpiresAt($expiresAt);
                    $em->persist($resetToken);
                    $em->flush();
                    // Envoyer l'email
                    $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], true);
                    $mail = (new Email())
                        ->from('no-reply@myassistant.com')
                        ->to($email)
                        ->subject('Réinitialisation de votre mot de passe')
                        ->html('<p>Pour réinitialiser votre mot de passe, cliquez sur le lien suivant :</p><p><a href="'.$resetUrl.'">Réinitialiser mon mot de passe</a></p>');
                    try {
                        $mailer->send($mail);
                        $emailSent = true;
                        $this->addFlash('success', 'Un email de réinitialisation a été envoyé si l\'adresse existe.');
                    } catch (TransportExceptionInterface $e) {
                        $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email.');
                    }
                } else {
                    $this->addFlash('success', 'Un email de réinitialisation a été envoyé si l\'adresse existe.');
                }
            } else {
                $this->addFlash('error', 'Veuillez renseigner une adresse email valide.');
            }
        }
        return $this->render('security/forgot_password.html.twig', [
            'emailSent' => $emailSent,
        ]);
    }
}
